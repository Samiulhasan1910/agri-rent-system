<?php
declare(strict_types=1);

class InspectionController extends Controller
{
    public function index(): void
    {
        $this->role('employee', 'admin');
        $this->view('inspection/index', [
            'title' => t('Return Inspection'),
            'returns' => (new Booking())->awaitingInspection(),
            'history' => (new Inspection())->history(20),
        ]);
    }

    public function store(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $condition = $this->input('condition');
        if (!array_key_exists($condition, Inspection::CONDITIONS)) {
            $this->fail(t('Condition selection failed.'), ['condition' => t('Please select the equipment condition.')]);
        }
        $hours = $this->input('usage_hours');
        if ($hours !== '' && (!is_numeric($hours) || (float)$hours < 0 || (float)$hours > 2000)) {
            $this->fail(t('Enter a valid number of hours'), ['usage_hours' => t('Enter a valid number of hours')]);
        }
        $comments = mb_substr($this->input('comments'), 0, 1000);
        try {
            $booking = (new Inspection())->record($this->int('booking_id'), $condition, $comments, $hours === '' ? null : (float)$hours, (int)$user['id']);
        } catch (DomainException $e) {
            $this->fail(t('Confirmation failed.') . ' ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Confirmation failed.'));
        }
        Notifier::send((int)$booking['farmer_id'], 'Rental completed', t('Your rental :code is complete. Please rate :equipment!', ['code' => $booking['booking_code'], 'equipment' => $booking['equipment_name']]), url('booking/show', ['id' => $booking['id']]), 'success');
        if ($condition !== 'good' && Settings::enabled('maintenance_alert')) {
            Notifier::staff('Maintenance needed', t(':equipment was returned as ":condition" (:code).', ['equipment' => $booking['equipment_name'], 'condition' => t(Inspection::CONDITIONS[$condition]), 'code' => $booking['booking_code']]), url('maintenance/health'), 'warning');
        }
        $message = t('Inspection submitted successfully.');
        if ($condition === 'damaged') {
            $message .= ' ' . t('The equipment has been marked unavailable.');
        }
        if ($hours === '') {
            $message .= ' ' . t('Usage hours were not provided, so the record was flagged.');
        }
        $this->success($message, null, ['removed' => $this->int('booking_id')]);
    }
}
