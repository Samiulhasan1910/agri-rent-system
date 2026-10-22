<?php
declare(strict_types=1);

class MaintenanceController extends Controller
{
    private Maintenance $maintenance;

    public function __construct()
    {
        $this->maintenance = new Maintenance();
    }

    /** Schedule maintenance (employee/admin). */
    public function index(): void
    {
        $this->role('employee', 'admin');
        $this->maintenance->startDue();
        $f = ['status' => in_array($this->input('status'), Maintenance::STATUSES, true) ? $this->input('status') : '', 'equipment' => $this->int('equipment')];
        $data = ['rows' => $this->maintenance->listAll($f), 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('maintenance/_list', $data);
        }
        $this->view('maintenance/index', $data + [
            'title' => t('Schedule Equipment Maintenance'),
            'equipment' => (new Equipment())->options(),
            'counts' => $this->maintenance->counts(),
            'alerts' => $this->maintenance->alerts('open', 8),
            'preselect' => $this->int('equipment_id'),
        ]);
    }

    public function store(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $v = new Validator($_POST);
        $v->required('equipment_id', t('Please select equipment'))
            ->required('scheduled_date', t('Please select a maintenance date'))->date('scheduled_date', t('Please select a maintenance date'))
            ->check('scheduled_date', !valid_date($v->value('scheduled_date')) || $v->value('scheduled_date') >= today(), t('The maintenance date cannot be in the past.'))
            ->date('end_date', t('Please select a valid completion date'))
            ->check('end_date', $v->value('end_date') === '' || $v->value('end_date') >= $v->value('scheduled_date'), t('Completion date cannot be before the start date'))
            ->in('type', ['routine', 'repair', 'inspection'], t('Please select a maintenance type'))
            ->required('notes', t('Please describe the maintenance work'));
        if ($v->fails()) {
            $this->fail(t('Maintenance scheduling failed.'), $v->errors());
        }
        $start = $v->value('scheduled_date');
        $end = $v->value('end_date') ?: $start;
        $status = $v->value('status') === 'in_progress' && $start === today() ? 'in_progress' : 'scheduled';
        try {
            $this->maintenance->schedule([
                'equipment_id' => (int)$v->value('equipment_id'),
                'scheduled_date' => $start,
                'end_date' => $end,
                'type' => $v->value('type') ?: 'routine',
                'notes' => mb_substr($v->value('notes'), 0, 500),
                'status' => $status,
                'created_by' => (int)$user['id'],
            ]);
            Health::evaluate((int)$v->value('equipment_id'));
        } catch (DomainException $e) {
            $this->fail(t('Maintenance scheduling failed.') . ' ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Maintenance scheduling failed.'));
        }
        $clashes = $this->maintenance->clashingBookings((int)$v->value('equipment_id'), $start, $end);
        $message = t('Maintenance scheduled successfully. The equipment cannot be booked during this period.');
        if ($clashes) {
            $message .= ' ' . t('Warning: :n existing booking(s) overlap this period (:codes).', ['n' => count($clashes), 'codes' => implode(', ', array_column($clashes, 'booking_code'))]);
        }
        $this->success($message, url('maintenance/index'));
    }

    public function status(): void
    {
        $this->role('employee', 'admin');
        $this->requirePost();
        $status = $this->input('status');
        try {
            $this->maintenance->setStatus($this->int('id'), $status);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        }
        $label = $status === 'in_progress' ? t('In Maintenance') : status_meta($status)[0];
        $this->success(t('Maintenance status updated to :status.', ['status' => $label]));
    }

    /* ==============================================================
     * Equipment health monitoring
     * ============================================================== */
    public function health(): void
    {
        $this->role('employee', 'admin');
        $this->maintenance->startDue();
        Health::evaluateAll();
        $f = ['health' => in_array($this->input('health'), ['good', 'needs_maintenance', 'under_maintenance', 'unknown'], true) ? $this->input('health') : '', 'q' => $this->input('q')];
        $equipment = new Equipment();
        $data = ['rows' => $equipment->healthList($f), 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('maintenance/_health_table', $data);
        }
        $this->view('maintenance/health', $data + [
            'title' => t('Equipment Health Monitoring'),
            'counts' => $equipment->healthCounts(),
            'alerts' => $this->maintenance->alerts('open', 20),
            'logs' => $this->maintenance->usageLogs(null, 15),
            'options' => $equipment->options(),
        ]);
    }

    public function logUsage(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $equipment = (new Equipment())->find($this->int('equipment_id'));
        if (!$equipment) {
            $this->fail(t('Please select equipment'), ['equipment_id' => t('Please select equipment')]);
        }
        $hours = $this->input('hours');
        if ($hours !== '' && (!is_numeric($hours) || (float)$hours < 0 || (float)$hours > 2000)) {
            $this->fail(t('Enter a valid number of hours'), ['hours' => t('Enter a valid number of hours')]);
        }
        Health::recordUsage((int)$equipment['id'], null, $hours === '' ? null : (float)$hours, (int)$user['id'], 'manual', mb_substr($this->input('note'), 0, 255) ?: null);
        $state = Health::evaluate((int)$equipment['id']);
        $message = $hours === ''
            ? t('Usage data is missing – the record has been flagged for follow-up.')
            : t('Usage logged. Current health: :state.', ['state' => status_meta($state)[0]]);
        $this->success($message, url('maintenance/health'));
    }

    public function resolveAlert(): void
    {
        $this->role('employee', 'admin');
        $this->requirePost();
        $equipmentId = $this->maintenance->alertEquipment($this->int('id'));
        if (!$equipmentId || !$this->maintenance->resolveAlert($this->int('id'))) {
            $this->fail(t('Alert not found or already resolved.'));
        }
        Health::evaluate($equipmentId);
        $this->success(t('Alert marked as resolved.'), null, ['removed' => $this->int('id')]);
    }
}
