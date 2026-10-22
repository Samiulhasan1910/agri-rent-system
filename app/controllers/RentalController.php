<?php
declare(strict_types=1);

/** Rental requests & tracking for employees and admins. */
class RentalController extends Controller
{
    private Booking $bookings;

    public function __construct()
    {
        $this->bookings = new Booking();
    }

    private function filters(): array
    {
        return [
            'q' => $this->input('q'),
            'equipment' => $this->int('equipment'),
            'status' => in_array($this->input('status'), ['pending', 'confirmed', 'delivered', 'returned', 'completed', 'cancelled', 'rejected'], true) ? $this->input('status') : '',
            'from' => valid_date($this->input('from')) ? $this->input('from') : '',
            'to' => valid_date($this->input('to')) ? $this->input('to') : '',
        ];
    }

    public function index(): void
    {
        $user = $this->role('employee', 'admin');
        $f = $this->filters();
        try {
            [$rows, $meta] = $this->bookings->listAll($f, max(1, $this->int('page', 1)));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to load rental list. Please try again.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f, 'user' => $user];
        if ($this->wantsPartial()) {
            $this->partial('rental/_table', $data);
        }
        $this->view('rental/index', $data + [
            'title' => t('Rental Tracking'),
            'equipment' => (new Equipment())->options(),
            'extensions' => (new Extension())->pending(),
            'counts' => $this->bookings->countByStatus(),
        ]);
    }

    /** Approve / reject / mark delivered / mark returned. */
    public function status(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $to = $this->input('status');
        $note = mb_substr($this->input('note'), 0, 255);
        if ($to === 'rejected' && $note === '') {
            $this->fail(t('Please give a reason for rejecting this request.'), ['note' => t('Please give a reason for rejecting this request.')]);
        }
        try {
            $before = $this->bookings->changeStatus($this->int('id'), $to, (int)$user['id'], $note !== '' ? $note : null);
        } catch (DomainException $e) {
            $this->fail(t('Failed to update rental status.') . ' ' . $e->getMessage(), [], null, 422, ['status' => $this->currentStatus()]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to update rental status.'), [], null, 500, ['status' => $this->currentStatus()]);
        }

        $booking = $this->bookings->find((int)$before['id']);
        $messages = [
            'confirmed' => [t('Booking confirmed'), t('Your booking :code for :equipment has been confirmed. Delivery is scheduled for :date.', ['code' => $booking['booking_code'], 'equipment' => $booking['equipment_name'], 'date' => fmt_date($booking['start_date'])]), 'success'],
            'rejected' => [t('Booking rejected'), t('Your booking :code was rejected: :note. Any payment will be refunded.', ['code' => $booking['booking_code'], 'note' => $note]), 'danger'],
            'delivered' => [t('Delivery completed'), t(':equipment (:code) has been delivered to :address.', ['equipment' => $booking['equipment_name'], 'code' => $booking['booking_code'], 'address' => $booking['delivery_address']]), 'success'],
            'returned' => [t('Return received'), t('We received :equipment (:code) back. Thank you! You can now rate this equipment once it is inspected.', ['equipment' => $booking['equipment_name'], 'code' => $booking['booking_code']]), 'info'],
        ];
        [$title, $text, $type] = $messages[$to];
        Notifier::send((int)$booking['farmer_id'], $title, $text, url('booking/show', ['id' => $booking['id']]), $type);
        if (in_array($to, ['confirmed', 'rejected'], true)) {
            Notifier::email($booking['farmer_email'], $title . ' - ' . $booking['booking_code'], $title, '<p>' . e($text) . '</p>', t('View Booking'), abs_url('booking/show', ['id' => $booking['id']]));
        }
        if ($to === 'delivered' && Settings::enabled('delivery_completion_alert')) {
            Notifier::admins('Delivery completed', t(':code was delivered to :address.', ['code' => $booking['booking_code'], 'address' => $booking['delivery_address']]), url('delivery/index'), 'success');
        }
        $this->success(t('Rental status updated to :status.', ['status' => status_meta($to)[0]]), null, [
            'status' => $booking['status'],
            'badge' => badge($booking['status']),
            'row' => View::partial('rental/_row', ['r' => $booking, 'user' => $user]),
        ]);
    }

    /** Current status for client-side synchronisation. */
    public function state(): void
    {
        $this->role('employee', 'admin');
        $status = $this->currentStatus();
        if ($status === null) {
            $this->json(['ok' => false, 'message' => t('Unable to display updated status.')], 404);
        }
        $this->json(['ok' => true, 'status' => $status, 'badge' => badge($status)]);
    }

    private function currentStatus(): ?string
    {
        $stmt = Database::connection()->prepare('SELECT status FROM bookings WHERE id=?');
        $stmt->execute([$this->int('id')]);
        $status = $stmt->fetchColumn();
        return $status === false ? null : (string)$status;
    }

    public function extension(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $decision = $this->input('decision');
        $note = mb_substr($this->input('note'), 0, 255);
        $ext = new Extension();
        $row = $ext->find($this->int('id'));
        if (!$row) {
            $this->fail(t('Extension request not found.'));
        }
        try {
            if ($decision === 'approved') {
                $result = $ext->approve((int)$row['id'], (int)$user['id'], $note ?: null);
            } elseif ($decision === 'rejected') {
                $ext->reject((int)$row['id'], (int)$user['id'], $note ?: null);
            } else {
                throw new DomainException(t('Invalid decision.'));
            }
        } catch (DomainException | InvalidArgumentException $e) {
            $this->fail(t('Extension request failed. Please complete the payment or try again.') . ' ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to update rental details. Please try again.'));
        }

        if ($decision === 'approved') {
            $calc = $result['calc'];
            $sent = Notifier::send((int)$row['farmer_id'], 'Extension approved', t('Your rental :code is extended to :date. Extra cost :cost has been added; please pay the balance.', [
                'code' => $row['booking_code'], 'date' => fmt_date($calc['new_end']), 'cost' => money($calc['extra_cost']),
            ]), url('payment/checkout', ['booking' => $row['booking_id']]), 'success');
            $sent = Notifier::email($row['farmer_email'], t('Extension approved - :code', ['code' => $row['booking_code']]), t('Extension approved'),
                '<p>' . e(t('Your rental :code is extended to :date. Extra cost :cost has been added; please pay the balance.', ['code' => $row['booking_code'], 'date' => fmt_date($calc['new_end']), 'cost' => money($calc['extra_cost'])])) . '</p>',
                t('Pay balance'), abs_url('payment/checkout', ['booking' => $row['booking_id']])) && $sent;
            $this->success($sent ? t('Extension approved and the farmer has been notified.') : t('Extension successful but failed to send confirmation notification.'));
        }
        Notifier::send((int)$row['farmer_id'], 'Extension rejected', t('Your extension request for :code was not approved. :note', ['code' => $row['booking_code'], 'note' => $note]), url('booking/show', ['id' => $row['booking_id']]), 'warning');
        $this->success(t('Extension request rejected.'));
    }

    public function export(): void
    {
        $this->role('employee', 'admin');
        $f = $this->filters();
        $where = ['1=1'];
        $params = [];
        foreach (['status' => 'b.status=?', 'equipment' => 'b.equipment_id=?', 'from' => 'b.end_date>=?', 'to' => 'b.start_date<=?'] as $key => $clause) {
            if (!empty($f[$key])) {
                $where[] = $clause;
                $params[] = $f[$key];
            }
        }
        if ($f['q'] !== '') {
            $where[] = '(b.booking_code LIKE ? OR u.name LIKE ? OR e.name LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        $rows = $this->bookings->export($where, $params);
        send_csv('rentals-' . date('Ymd') . '.csv', ['Booking', 'Farmer', 'Phone', 'Equipment', 'Start', 'End', 'Days', 'Total (BDT)', 'Paid (BDT)', 'Payment', 'Status', 'Delivery'],
            array_map(static fn($b) => [$b['booking_code'], $b['farmer_name'], $b['farmer_phone'], $b['equipment_name'], $b['start_date'], $b['end_date'], $b['days'],
                number_format((float)$b['total_amount'], 2, '.', ''), number_format((float)$b['paid_amount'], 2, '.', ''), Booking::paymentState($b), $b['status'], $b['delivery_status'] ?? ''], $rows));
    }
}
