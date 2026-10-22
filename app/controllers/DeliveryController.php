<?php
declare(strict_types=1);

class DeliveryController extends Controller
{
    private Delivery $deliveries;

    public function __construct()
    {
        $this->deliveries = new Delivery();
    }

    public function index(): void
    {
        $this->role('admin');
        $f = [
            'q' => $this->input('q'),
            'status' => in_array($this->input('status'), ['pending', 'assigned', 'in_progress', 'delivered', 'returned'], true) ? $this->input('status') : '',
            'team' => $this->int('team'),
            'date' => valid_date($this->input('date')) ? $this->input('date') : '',
        ];
        try {
            [$rows, $meta] = $this->deliveries->paginateList($f, max(1, $this->int('page', 1)));
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Unable to load delivery status.'), [], url('dashboard/index'));
        }
        $data = ['rows' => $rows, 'meta' => $meta, 'f' => $f];
        if ($this->wantsPartial()) {
            $this->partial('delivery/_table', $data);
        }
        $selected = $rows ? $this->deliveries->find((int)$rows[0]['id']) : null;
        $this->view('delivery/index', $data + [
            'title' => t('Delivery Management'),
            'teams' => $this->deliveries->teams(),
            'counts' => $this->deliveries->counts(),
            'selected' => $selected,
            'updates' => $selected ? $this->deliveries->updates((int)$selected['id']) : [],
        ]);
    }

    /** AJAX side panel with tracking progress and team contacts. */
    public function show(): void
    {
        $this->role('admin');
        $delivery = $this->deliveries->find($this->int('id'));
        if (!$delivery) {
            $this->json(['ok' => false, 'message' => t('Failed to load delivery progress.')], 404);
        }
        $this->json(['ok' => true, 'html' => View::partial('delivery/_panel', [
            'd' => $delivery,
            'updates' => $this->deliveries->updates((int)$delivery['id']),
            'teams' => $this->deliveries->teams(true),
        ])]);
    }

    public function assign(): void
    {
        $user = $this->role('admin');
        $this->requirePost();
        $team = $this->deliveries->team($this->int('team_id'));
        $date = $this->input('scheduled_date');
        $errors = [];
        if (!$team || $team['status'] !== 'active') {
            $errors['team_id'] = t('Please select a delivery team.');
        }
        if (!valid_date($date)) {
            $errors['scheduled_date'] = t('Please select a delivery date.');
        }
        if ($errors) {
            $this->fail(t('Please fix the highlighted fields.'), $errors);
        }
        try {
            $delivery = $this->deliveries->assign($this->int('id'), (int)$team['id'], $date, mb_substr($this->input('estimated_time'), 0, 40), (int)$user['id']);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        }
        Notifier::send((int)$delivery['farmer_id'], 'Delivery scheduled', t(':equipment (:code) will be delivered on :date by :team.', [
            'equipment' => $delivery['equipment_name'], 'code' => $delivery['booking_code'], 'date' => fmt_date($date), 'team' => $team['name'],
        ]), url('booking/show', ['id' => $delivery['booking_id']]));
        $this->success(t('Delivery assigned to :team.', ['team' => $team['name']]), null, $this->panelPayload($delivery));
    }

    public function status(): void
    {
        $user = $this->role('admin');
        $this->requirePost();
        $status = $this->input('status');
        try {
            $delivery = $this->deliveries->setStatus($this->int('id'), $status, $this->input('note'), (int)$user['id']);
        } catch (DomainException $e) {
            $this->fail(t('Failed to update delivery status.') . ' ' . $e->getMessage());
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Failed to update delivery status.'));
        }
        $labels = ['in_progress' => t('is on the way'), 'delivered' => t('has been delivered'), 'returned' => t('has been picked up (returned)')];
        Notifier::send((int)$delivery['farmer_id'], 'Delivery update', t(':equipment (:code) :status.', ['equipment' => $delivery['equipment_name'], 'code' => $delivery['booking_code'], 'status' => $labels[$status] ?? $status]),
            url('booking/show', ['id' => $delivery['booking_id']]), $status === 'delivered' ? 'success' : 'info');
        $message = t('Delivery status updated.');
        if ($status === 'delivered' && Settings::enabled('delivery_completion_alert')) {
            $sent = Notifier::admins('Delivery completed', t(':code was delivered to :address by :team.', ['code' => $delivery['booking_code'], 'address' => $delivery['address'], 'team' => $delivery['team_name'] ?? '-']), url('delivery/index'), 'success');
            if (!$sent) {
                error_log('Delivery notification failed for delivery ' . $delivery['id']);
                $message .= ' ' . t('Delivery notification could not be sent.');
            }
        }
        $this->success($message, null, $this->panelPayload($delivery));
    }

    private function panelPayload(array $delivery): array
    {
        return [
            'html' => View::partial('delivery/_panel', ['d' => $delivery, 'updates' => $this->deliveries->updates((int)$delivery['id']), 'teams' => $this->deliveries->teams(true)]),
            'row' => View::partial('delivery/_row', ['r' => $delivery]),
            'id' => (int)$delivery['id'],
        ];
    }

    public function teamSave(): void
    {
        $this->role('admin');
        $this->requirePost();
        $id = $this->int('id');
        $v = new Validator($_POST);
        $v->required('name', t('Team name is required'))->check('name', !$this->deliveries->teamNameExists($v->value('name'), $id), t('A team with this name already exists'))
            ->required('leader_name', t('Team leader is required'))
            ->required('phone', t('Phone number is required'))->regex('phone', '/^\+?[0-9\s-]{8,18}$/', t('Invalid phone number'))
            ->required('email', t('Email is required'))->email('email', t('Invalid email address'));
        if ($v->fails()) {
            $this->fail(t('Please fix the highlighted fields.'), $v->errors());
        }
        $this->deliveries->saveTeam([
            'name' => $v->value('name'),
            'leader_name' => $v->value('leader_name'),
            'phone' => $v->value('phone'),
            'email' => mb_strtolower($v->value('email')),
            'vehicle' => $v->value('vehicle') ?: null,
            'status' => $v->value('status') === 'inactive' ? 'inactive' : 'active',
        ], $id);
        $this->success(t('Delivery team saved.'), url('delivery/index'));
    }
}
