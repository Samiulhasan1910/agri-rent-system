<?php
declare(strict_types=1);

class BookingController extends Controller
{
    public const CANCEL_REASONS = ['Change of plans', 'Price is too high', 'Booked by mistake', 'Found another equipment', 'Weather conditions', 'Other'];

    private Booking $bookings;

    public function __construct()
    {
        $this->bookings = new Booking();
    }

    /** Farmers must be email-verified (always) and account-verified before booking. */
    private function verifiedFarmer(): array
    {
        $user = $this->role('farmer');
        if ($user['verification_status'] !== 'approved') {
            $this->fail(t('Account verification required before booking.'), [], url('profile/index'), 403);
        }
        return $user;
    }

    private function ownedBooking(array $user): array
    {
        $booking = $this->bookings->find($this->int('id'));
        if (!$booking || ($user['role'] === 'farmer' && (int)$booking['farmer_id'] !== (int)$user['id'])) {
            throw new HttpException(404, t('Booking not found.'));
        }
        return $booking;
    }

    /* ==============================================================
     * Create
     * ============================================================== */
    public function confirm(): void
    {
        $user = $this->verifiedFarmer();
        $equipment = (new Equipment())->find($this->int('equipment_id'));
        if (!$equipment) {
            throw new HttpException(404, t('Equipment not found.'));
        }
        $back = url('equipment/show', ['id' => $equipment['id'], 'start_date' => $this->input('start_date'), 'end_date' => $this->input('end_date')]);
        try {
            $quote = $this->bookings->quote($equipment, $this->input('start_date'), $this->input('end_date'));
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), [], $back);
        } catch (DomainException $e) {
            $this->fail(t('Unable to calculate price. Please try again.'), [], $back);
        }
        if ($quote['block_reason']) {
            $this->fail($quote['block_reason'], [], $back);
        }
        if ($quote['maintenance']) {
            $this->fail(t('This equipment is scheduled for maintenance from :from to :to. Please choose other dates.', ['from' => fmt_date($quote['maintenance']['from']), 'to' => fmt_date($quote['maintenance']['to'])]), [], $back);
        }
        if ($quote['available_units'] < 1) {
            $this->fail(t('The equipment is already fully booked for these dates. Please choose another period.'), [], $back);
        }
        $this->view('booking/confirm', [
            'title' => t('Confirm Your Booking'),
            'equipment' => $equipment,
            'quote' => $quote,
            'start' => $this->input('start_date'),
            'end' => $this->input('end_date'),
            'user' => $user,
        ]);
    }

    public function store(): void
    {
        $user = $this->verifiedFarmer();
        $this->requirePost();
        $address = $this->input('delivery_address');
        if ($address === '' || mb_strlen($address) > 255) {
            $this->fail(t('Please enter a delivery address (max 255 characters).'), ['delivery_address' => t('Please enter a delivery address (max 255 characters).')]);
        }
        $uuid = is_uuid($this->input('client_uuid')) ? strtolower($this->input('client_uuid')) : null;
        if ($uuid && ($existing = $this->bookings->findByUuid($uuid))) {
            $this->success(t('Booking request already received.'), url('payment/checkout', ['booking' => $existing['id']]));
        }
        try {
            $id = $this->bookings->create((int)$user['id'], $this->int('equipment_id'), $this->input('start_date'), $this->input('end_date'), $address, $this->input('notes'), $uuid);
        } catch (InvalidArgumentException | DomainException $e) {
            $this->fail(t('Booking could not be completed. Please try again.') . ' ' . $e->getMessage());
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to save booking details.'));
        }
        $booking = $this->bookings->find($id);
        Notifier::staff('New rental request', t(':code for :equipment (:from to :to) is waiting for approval.', [
            'code' => $booking['booking_code'], 'equipment' => $booking['equipment_name'], 'from' => fmt_date($booking['start_date']), 'to' => fmt_date($booking['end_date']),
        ]), url('rental/index'));
        $emailed = $this->sendBookingEmail($booking);
        $message = $emailed ? t('Booking request submitted! Booking ID: :code. Please choose a payment method.', ['code' => $booking['booking_code']]) : t('Booking confirmed, but email could not be sent.');
        if ($this->isAjax()) {
            Session::flash($emailed ? 'success' : 'warning', $message);
            $this->json(['ok' => true, 'message' => $message, 'redirect' => url('payment/checkout', ['booking' => $id]), 'booking_id' => $id]);
        }
        Session::flash($emailed ? 'success' : 'warning', $message);
        $this->redirect('payment/checkout', ['booking' => $id]);
    }

    private function sendBookingEmail(array $b): bool
    {
        $rows = [
            t('Booking ID') => $b['booking_code'],
            t('Equipment') => $b['equipment_name'],
            t('Rental Period') => fmt_date($b['start_date']) . ' - ' . fmt_date($b['end_date']) . ' (' . t(':n days', ['n' => $b['days']]) . ')',
            t('Rate') => money($b['price_per_day']) . ' / ' . t('day'),
            t('Delivery Charge') => money($b['delivery_charge']),
            t('Total Amount') => money($b['total_amount']),
            t('Delivery Address') => $b['delivery_address'],
        ];
        $table = '<table style="width:100%;border-collapse:collapse;margin:12px 0">';
        foreach ($rows as $k => $v) {
            $table .= '<tr><td style="padding:7px 0;color:#6b7c72;border-bottom:1px solid #eef3ef">' . e($k) . '</td><td style="padding:7px 0;text-align:right;font-weight:600;border-bottom:1px solid #eef3ef">' . e($v) . '</td></tr>';
        }
        $table .= '</table>';
        $body = '<p>' . e(t('Hi :name, your booking request has been received. Our team will confirm it shortly.', ['name' => $b['farmer_name']])) . '</p>' . $table;
        return Notifier::email($b['farmer_email'], t('Booking :code received', ['code' => $b['booking_code']]), t('Booking details'), $body, t('View Booking'), abs_url('booking/show', ['id' => $b['id']]));
    }

    /* ==============================================================
     * Lists & details
     * ============================================================== */
    public function index(): void
    {
        $user = $this->role('farmer');
        $tab = in_array($this->input('tab'), ['active', 'pending', 'cancelled', 'completed'], true) ? $this->input('tab') : 'all';
        [$rows, $meta] = $this->bookings->forFarmer((int)$user['id'], ['tab' => $tab, 'q' => $this->input('q')], max(1, $this->int('page', 1)));
        $data = ['rows' => $rows, 'meta' => $meta, 'tab' => $tab, 'q' => $this->input('q')];
        if ($this->wantsPartial()) {
            $this->partial('booking/_list', $data);
        }
        $this->view('booking/index', $data + ['title' => t('My Bookings')]);
    }

    public function show(): void
    {
        $user = $this->auth();
        $booking = $this->ownedBooking($user);
        $this->view('booking/show', [
            'title' => t('Booking Details'),
            'b' => $booking,
            'user' => $user,
            'payments' => (new Payment())->forBooking((int)$booking['id']),
            'extensions' => (new Extension())->forBooking((int)$booking['id']),
            'updates' => $booking['delivery_id'] ? (new Delivery())->updates((int)$booking['delivery_id']) : [],
            'inspection' => (new Inspection())->forBooking((int)$booking['id']),
            'contact' => (new User())->supportContact(),
        ]);
    }

    /* ==============================================================
     * Cancellation (only before delivery)
     * ============================================================== */
    public function cancel(): void
    {
        $user = $this->role('farmer');
        $booking = $this->ownedBooking($user);
        if (!$this->isPost()) {
            if (!Booking::canCancel($booking)) {
                $this->fail(t('Cancellation not allowed after delivery.'), [], url('booking/show', ['id' => $booking['id']]));
            }
            $this->view('booking/cancel', ['title' => t('Rental Cancellation Request'), 'b' => $booking, 'reasons' => self::CANCEL_REASONS]);
            return;
        }
        $this->requirePost();
        $reason = $this->input('reason');
        if ($reason === '' || !in_array($reason, self::CANCEL_REASONS, true)) {
            $this->fail(t('Cancellation reason required.'), ['reason' => t('Cancellation reason required.')]);
        }
        $details = mb_substr($this->input('details'), 0, 500);
        if ($reason === 'Other' && $details === '') {
            $this->fail(t('Please describe your reason.'), ['details' => t('Please describe your reason.')]);
        }
        try {
            $result = $this->bookings->cancel((int)$booking['id'], (int)$user['id'], $reason, $details);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        }
        Notifier::admins('New cancellation', t(':code was cancelled by :name (:reason).', ['code' => $booking['booking_code'], 'name' => $user['name'], 'reason' => t($reason)]), url('rental/index'), 'warning');
        if ($result['refunded'] > 0) {
            Notifier::send((int)$user['id'], 'Refund initiated', t('A refund of :amount for :code has been initiated to your original payment method.', ['amount' => money($result['refunded']), 'code' => $booking['booking_code']]), url('payment/history'), 'success');
        }
        $this->success(t('Booking cancelled successfully.'), url('booking/cancelled', ['id' => $booking['id']]));
    }

    public function cancelled(): void
    {
        $user = $this->role('farmer');
        $booking = $this->ownedBooking($user);
        $this->view('booking/cancelled', ['title' => t('Booking Cancelled Successfully!'), 'b' => $booking]);
    }

    /* ==============================================================
     * Rental extension
     * ============================================================== */
    public function extend(): void
    {
        $user = $this->role('farmer');
        $booking = $this->ownedBooking($user);
        if (!$this->isPost()) {
            if (!Booking::canExtend($booking)) {
                $msg = $booking['pending_extension_id'] ? t('This booking already has an extension request waiting for approval.') : t('Extension request cannot be processed. Please try again before the rental expires.');
                $this->fail($msg, [], url('booking/show', ['id' => $booking['id']]));
            }
            $this->view('booking/extend', ['title' => t('Rental Extension Request'), 'b' => $booking]);
            return;
        }
        $this->requirePost();
        $reason = $this->input('reason');
        $errors = [];
        if ($this->input('new_end_date') === '') {
            $errors['new_end_date'] = t('Please select a new return date.');
        }
        if ($reason === '') {
            $errors['reason'] = t('Please explain why you need the extension.');
        }
        if ($errors) {
            $this->fail(t('Please fix the highlighted fields.'), $errors);
        }
        $ext = new Extension();
        try {
            $calc = $ext->calculate($booking, $this->input('new_end_date'));
            $ext->create($booking, $calc, $reason);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), ['new_end_date' => $e->getMessage()]);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        } catch (PDOException $e) {
            $this->fail(t('Failed to update rental details. Please try again.'));
        }
        Notifier::staff('Extension requested', t(':code requests an extension to :date (+:days days, :cost).', [
            'code' => $booking['booking_code'], 'date' => fmt_date($calc['new_end']), 'days' => $calc['extra_days'], 'cost' => money($calc['extra_cost']),
        ]), url('rental/index'));
        $this->success(t('Extension request submitted. You will be notified once it is approved.'), url('booking/show', ['id' => $booking['id']]));
    }

    /** AJAX: extra days/cost and availability for an extension. */
    public function extensionQuote(): void
    {
        $user = $this->role('farmer');
        $booking = $this->ownedBooking($user);
        try {
            $calc = (new Extension())->calculate($booking, $this->input('new_end_date'));
        } catch (InvalidArgumentException | DomainException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $this->json(['ok' => true] + $calc);
    }

    /* ==============================================================
     * Rental history & downloadable report
     * ============================================================== */
    private function historyFilters(array $user): array
    {
        $f = [
            'q' => $this->input('q'),
            'status' => in_array($this->input('status'), ['pending', 'confirmed', 'delivered', 'returned', 'completed', 'cancelled', 'rejected'], true) ? $this->input('status') : '',
            'from' => $this->input('from'),
            'to' => $this->input('to'),
        ];
        $error = '';
        if (($f['from'] !== '' && !valid_date($f['from'])) || ($f['to'] !== '' && !valid_date($f['to'])) || ($f['from'] !== '' && $f['to'] !== '' && $f['to'] < $f['from'])) {
            $error = t('Filter operation failed.') . ' ' . t('End date cannot be earlier than start date.');
        }
        $where = ['b.farmer_id=?'];
        $params = [(int)$user['id']];
        if ($f['q'] !== '') {
            $where[] = '(e.name LIKE ? OR b.booking_code LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        if ($f['status'] !== '') {
            $where[] = 'b.status=?';
            $params[] = $f['status'];
        }
        if (!$error && $f['from'] !== '') {
            $where[] = 'b.end_date>=?';
            $params[] = $f['from'];
        }
        if (!$error && $f['to'] !== '') {
            $where[] = 'b.start_date<=?';
            $params[] = $f['to'];
        }
        return [$f, $where, $params, $error];
    }

    public function history(): void
    {
        $user = $this->role('farmer');
        [$f, $where, $params, $error] = $this->historyFilters($user);
        $rows = [];
        $meta = paginate(0, 1);
        if (!$error) {
            try {
                $all = $this->bookings->export($where, $params);
                $meta = paginate(count($all), max(1, $this->int('page', 1)), 8);
                $rows = array_slice($all, $meta['offset'], $meta['per']);
            } catch (Throwable $e) {
                $error = t('Unable to load rental data.');
            }
        }
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f, 'error' => $error];
        if ($this->wantsPartial()) {
            $this->partial('booking/_history', $data);
        }
        $this->view('booking/history', $data + ['title' => t('Rental History')]);
    }

    public function download(): void
    {
        $user = $this->role('farmer');
        [$f, $where, $params, $error] = $this->historyFilters($user);
        $back = url('booking/history', array_filter($f));
        if ($error) {
            $this->fail($error, [], $back);
        }
        try {
            $rows = $this->bookings->export($where, $params);
        } catch (Throwable $e) {
            $this->fail(t('Unable to load rental data.'), [], $back);
        }
        if (!$rows) {
            $this->fail(t('There are no rentals to include in the report.'), [], $back);
        }
        foreach ($rows as $r) {
            if ($r['equipment_name'] === null || $r['equipment_name'] === '' || $r['days'] === null || $r['total_amount'] === null) {
                $this->fail(t('Incomplete report data.'), [], $back);
            }
        }
        // integrity check: listed totals must equal the stored records
        $stmt = Database::connection()->prepare('SELECT COALESCE(SUM(b.total_amount),0), COUNT(*) FROM bookings b JOIN equipment e ON e.id=b.equipment_id JOIN users u ON u.id=b.farmer_id WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);
        [$dbTotal, $dbCount] = $stmt->fetch(PDO::FETCH_NUM);
        $sum = array_sum(array_map(static fn($r) => (float)$r['total_amount'], $rows));
        if ((int)$dbCount !== count($rows) || abs($sum - (float)$dbTotal) > 0.009) {
            $this->fail(t('Report data mismatch detected.'), [], $back);
        }

        if ($this->input('format') === 'csv') {
            send_csv('rental-history-' . date('Ymd') . '.csv', ['Booking ID', 'Equipment', 'Start date', 'End date', 'Duration (days)', 'Rate/day (BDT)', 'Total (BDT)', 'Paid (BDT)', 'Status'],
                array_map(static fn($r) => [$r['booking_code'], $r['equipment_name'], $r['start_date'], $r['end_date'], $r['days'], number_format((float)$r['price_per_day'], 2, '.', ''),
                    number_format((float)$r['total_amount'], 2, '.', ''), number_format((float)$r['paid_amount'], 2, '.', ''), $r['status']], $rows));
        }
        try {
            $pdf = new Pdf(true);
            $pdf->addPage();
            $pdf->header('Rental History Report', 'Generated ' . date('d M Y, h:i A'));
            $pdf->font(10, true);
            $pdf->text($pdf->margin, $pdf->y, 'Farmer: ' . $user['name'] . '   |   ' . $user['email'] . '   |   ' . $user['phone']);
            $pdf->font(9);
            $pdf->text($pdf->margin, $pdf->y + 15, 'Filters: ' . ($f['from'] ?: 'any') . ' to ' . ($f['to'] ?: 'any') . '  |  Status: ' . ($f['status'] ?: 'all') . '  |  ' . count($rows) . ' rentals');
            $pdf->y += 32;
            $pdf->table([
                ['label' => 'Booking ID', 'width' => 70, 'bold' => true], ['label' => 'Equipment', 'width' => 170], ['label' => 'Start', 'width' => 75],
                ['label' => 'End', 'width' => 75], ['label' => 'Days', 'width' => 45, 'align' => 'R'], ['label' => 'Rate/day', 'width' => 80, 'align' => 'R'],
                ['label' => 'Total', 'width' => 90, 'align' => 'R'], ['label' => 'Paid', 'width' => 85, 'align' => 'R'], ['label' => 'Status', 'width' => 70],
            ], array_map(static fn($r) => [$r['booking_code'], $r['equipment_name'], fmt_date($r['start_date']), fmt_date($r['end_date']), $r['days'],
                'Tk ' . number_format((float)$r['price_per_day'], 2), 'Tk ' . number_format((float)$r['total_amount'], 2), 'Tk ' . number_format((float)$r['paid_amount'], 2), ucfirst($r['status'])], $rows));
            $pdf->y += 16;
            $pdf->font(11, true);
            $pdf->text($pdf->margin, $pdf->y, 'Total rental value: Tk ' . number_format($sum, 2), [20, 83, 45]);
            $pdf->download('rental-history-' . date('Ymd') . '.pdf');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Report generation failed.'), [], $back);
        }
    }
}
