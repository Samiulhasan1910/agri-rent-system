<?php
declare(strict_types=1);

/**
 * Complaints & support tickets. Farmers see only their own tickets; complaint details
 * are restricted to authorised staff (admin & employees).
 */
class ComplaintController extends Controller
{
    private Complaint $complaints;

    public function __construct()
    {
        $this->complaints = new Complaint();
    }

    public function index(): void
    {
        $user = $this->auth();
        if ($user['role'] === 'farmer') {
            $this->view('complaint/farmer', [
                'title' => t('Complaint'),
                'rows' => $this->complaints->forUser((int)$user['id']),
                'bookings' => (new Booking())->forFarmer((int)$user['id'], [], 1, 30)[0],
                'categories' => Complaint::CATEGORIES,
                'preBooking' => $this->int('booking'),
            ]);
            return;
        }
        $f = [
            'q' => $this->input('q'),
            'status' => in_array($this->input('status'), Complaint::STATUSES, true) ? $this->input('status') : '',
            'category' => in_array($this->input('category'), Complaint::CATEGORIES, true) ? $this->input('category') : '',
            'priority' => in_array($this->input('priority'), ['low', 'medium', 'high'], true) ? $this->input('priority') : '',
        ];
        [$rows, $meta] = $this->complaints->paginateList($f, max(1, $this->int('page', 1)));
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('complaint/_table', $data);
        }
        $this->view('complaint/manage', $data + ['title' => t('Manage Complaints'), 'categories' => Complaint::CATEGORIES]);
    }

    public function store(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $errors = [];
        $category = $this->input('category');
        if ($category === '' || !in_array($category, Complaint::CATEGORIES, true)) {
            $errors['category'] = t('Complaint category selection failed.');
        }
        if ($this->input('subject') === '') {
            $errors['subject'] = t('Subject is required');
        }
        if ($this->input('description') === '') {
            $errors['description'] = t('Description cannot be empty.');
        }
        if ($errors) {
            $this->fail(reset($errors), $errors);
        }
        $bookingId = $this->int('booking_id') ?: null;
        if ($bookingId) {
            $booking = (new Booking())->find($bookingId);
            if (!$booking || (int)$booking['farmer_id'] !== (int)$user['id']) {
                $bookingId = null;
            }
        }
        $attachment = null;
        if (Upload::has('attachment')) {
            try {
                $attachment = Upload::image('attachment', 'complaints');
            } catch (RuntimeException $e) {
                $this->fail($e->getMessage(), ['attachment' => $e->getMessage()]);
            }
        }
        try {
            $created = $this->complaints->create([
                'user_id' => (int)$user['id'],
                'booking_id' => $bookingId,
                'category' => $category,
                'priority' => in_array($this->input('priority'), ['low', 'medium', 'high'], true) ? $this->input('priority') : 'medium',
                'subject' => mb_substr($this->input('subject'), 0, 180),
                'description' => mb_substr($this->input('description'), 0, 3000),
                'attachment' => $attachment,
            ]);
        } catch (Throwable $e) {
            Upload::delete($attachment);
            error_log($e->getMessage());
            $this->fail(t('Ticket ID generation failed.'));
        }
        if (Settings::enabled('complaint_ticket_alert')) {
            Notifier::staff('New complaint', t(':ticket: :subject (:category) from :name.', ['ticket' => $created['ticket_no'], 'subject' => $this->input('subject'), 'category' => t($category), 'name' => $user['name']]), url('complaint/show', ['id' => $created['id']]), 'warning');
        }
        $this->success(t('Complaint Submitted Successfully!') . ' ' . t('Ticket ID: :ticket', ['ticket' => $created['ticket_no']]), url('complaint/show', ['id' => $created['id'], 'new' => 1]));
    }

    public function show(): void
    {
        $user = $this->auth();
        $row = $this->complaints->find($this->int('id'));
        if (!$row || ($user['role'] === 'farmer' && (int)$row['user_id'] !== (int)$user['id'])) {
            throw new HttpException(404, t('Complaint not found.'));
        }
        if ($this->isAjax()) {
            $this->json(['ok' => true, 'complaint' => [
                'id' => (int)$row['id'], 'ticket_no' => $row['ticket_no'], 'subject' => $row['subject'], 'category' => $row['category'],
                'priority' => $row['priority'], 'status' => $row['status'], 'description' => $row['description'], 'admin_response' => $row['admin_response'],
                'user_name' => $row['user_name'], 'booking_code' => $row['booking_code'], 'created_at' => fmt_datetime($row['created_at']),
            ]]);
        }
        $this->view('complaint/show', ['title' => $row['ticket_no'], 'c' => $row, 'isNew' => $this->int('new') === 1, 'user' => $user]);
    }

    public function update(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $row = $this->complaints->find($this->int('id'));
        $status = $this->input('status');
        if (!$row || !in_array($status, Complaint::STATUSES, true)) {
            $this->fail(t('Complaint status update failed.'));
        }
        try {
            $this->complaints->updateStatus((int)$row['id'], $status, mb_substr($this->input('response'), 0, 2000), (int)$user['id']);
        } catch (Throwable $e) {
            $this->fail(t('Complaint status update failed.'));
        }
        Notifier::send((int)$row['user_id'], 'Support ticket updated', t('Ticket :ticket is now :status.', ['ticket' => $row['ticket_no'], 'status' => status_meta($status)[0]]), url('complaint/show', ['id' => $row['id']]), $status === 'resolved' ? 'success' : 'info');
        $this->success(t('Complaint status updated.'), null, ['status' => $status, 'badge' => badge($status), 'id' => (int)$row['id']]);
    }
}
