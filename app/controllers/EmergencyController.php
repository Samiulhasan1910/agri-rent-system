<?php
declare(strict_types=1);

/** Emergency equipment requests: GPS/district location -> nearest available equipment. */
class EmergencyController extends Controller
{
    private Emergency $emergency;

    public function __construct()
    {
        $this->emergency = new Emergency();
    }

    public function index(): void
    {
        $user = $this->auth();
        if ($user['role'] === 'farmer') {
            $this->view('emergency/farmer', [
                'title' => t('Emergency Equipment Request'),
                'rows' => $this->emergency->forFarmer((int)$user['id']),
                'categories' => (new Category())->active(),
                'districts' => array_keys(Emergency::DISTRICTS),
                'radius' => (float)setting('emergency_radius_km', 80),
            ]);
            return;
        }
        $status = in_array($this->input('status'), ['pending', 'approved', 'rejected', 'completed'], true) ? $this->input('status') : '';
        $this->view('emergency/manage', ['title' => t('Emergency Requests'), 'rows' => $this->emergency->listAll($status), 'status' => $status]);
    }

    public function store(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        $result = $this->createRequest($user, $_POST);
        if (!$result['ok']) {
            $this->fail($result['message'], $result['errors'] ?? [], null, $result['status'] ?? 422, ['code' => $result['code'] ?? '']);
        }
        $this->success($result['message'], null, ['html' => $result['html'], 'request' => $result['request']]);
    }

    /** Shared with offline sync. */
    public function createRequest(array $user, array $input): array
    {
        $category = (new Category())->find((int)($input['category_id'] ?? 0));
        if (!$category) {
            return ['ok' => false, 'message' => t('Please select the equipment type you need.'), 'errors' => ['category_id' => t('Please select the equipment type you need.')]];
        }
        $lat = $input['latitude'] ?? '';
        $lng = $input['longitude'] ?? '';
        $label = trim((string)($input['location_label'] ?? ''));
        $district = trim((string)($input['district'] ?? ''));
        if ((!is_numeric($lat) || !is_numeric($lng)) && isset(Emergency::DISTRICTS[$district])) {
            [$lat, $lng] = Emergency::DISTRICTS[$district];
            $label = $label !== '' ? $label : $district;
        }
        if (!is_numeric($lat) || !is_numeric($lng) || abs((float)$lat) > 90 || abs((float)$lng) > 180) {
            return ['ok' => false, 'code' => 'location_required', 'message' => t('Please enable GPS/location services to find the nearest equipment, or choose your district.')];
        }
        $neededBy = str_replace('T', ' ', (string)($input['needed_by'] ?? ''));
        if ($neededBy === '' || strtotime($neededBy) === false) {
            $neededBy = date('Y-m-d H:i:s', time() + 6 * 3600);
        } elseif (strtotime($neededBy) < time() - 3600) {
            return ['ok' => false, 'message' => t('The "needed by" time cannot be in the past.'), 'errors' => ['needed_by' => t('The "needed by" time cannot be in the past.')]];
        }

        $radius = max(1.0, (float)setting('emergency_radius_km', 80));
        $candidates = (new Equipment())->nearest((int)$category['id'], (float)$lat, (float)$lng, $radius);
        $free = Availability::freeUnits($candidates, today(), today());
        $match = null;
        foreach ($candidates as $c) {
            if (($free[(int)$c['id']] ?? 0) > 0) {
                $match = $c;
                break;
            }
        }
        if (!$match) {
            return ['ok' => false, 'code' => 'none_nearby', 'status' => 404, 'message' => t('No equipment available nearby') . ' (' . t('within :km km', ['km' => (int)$radius]) . ')'];
        }

        $uuid = is_uuid($input['client_uuid'] ?? '') ? strtolower((string)$input['client_uuid']) : null;
        if ($uuid && ($existing = $this->emergency->findByUuid($uuid))) {
            return ['ok' => true, 'message' => t('Emergency request already received.'), 'html' => View::partial('emergency/_result', ['r' => $existing]), 'request' => ['id' => (int)$existing['id']]];
        }
        try {
            $created = $this->emergency->create([
                'farmer_id' => (int)$user['id'],
                'category_id' => (int)$category['id'],
                'equipment_id' => (int)$match['id'],
                'latitude' => round((float)$lat, 6),
                'longitude' => round((float)$lng, 6),
                'location_label' => mb_substr($label !== '' ? $label : ($user['address'] ?: t('Current location')), 0, 150),
                'distance_km' => round((float)$match['distance_km'], 2),
                'needed_by' => date('Y-m-d H:i:s', strtotime($neededBy)),
                'details' => mb_substr(trim((string)($input['details'] ?? '')), 0, 500) ?: null,
                'client_uuid' => $uuid,
            ]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return ['ok' => false, 'code' => 'retry', 'status' => 500, 'message' => t('The request could not be sent. Please try again.')];
        }
        Notifier::staff('Emergency request', t(':name needs a :type urgently near :location. Nearest: :equipment (:km km).', [
            'name' => $user['name'], 'type' => $category['name'], 'location' => $label ?: $user['address'], 'equipment' => $match['name'], 'km' => number_format((float)$match['distance_km'], 1),
        ]), url('emergency/index'), 'danger');
        $request = $this->emergency->find($created['id']);
        return [
            'ok' => true,
            'message' => t('Emergency request sent! :equipment is :km km away. Our team will contact you shortly.', ['equipment' => $match['name'], 'km' => number_format((float)$match['distance_km'], 1)]),
            'html' => View::partial('emergency/_result', ['r' => $request]),
            'request' => ['id' => $created['id'], 'code' => $created['code']],
        ];
    }

    public function status(): void
    {
        $user = $this->role('employee', 'admin');
        $this->requirePost();
        $request = $this->emergency->find($this->int('id'));
        $status = $this->input('status');
        if (!$request) {
            $this->fail(t('Request not found.'));
        }
        try {
            $this->emergency->setStatus((int)$request['id'], $status, (int)$user['id']);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        }
        $texts = [
            'approved' => t('Your emergency request :code was approved. :equipment is being arranged; our team will call you.', ['code' => $request['request_code'], 'equipment' => $request['equipment_name'] ?? '']),
            'rejected' => t('Sorry, your emergency request :code could not be fulfilled.', ['code' => $request['request_code']]),
            'completed' => t('Your emergency request :code is completed.', ['code' => $request['request_code']]),
        ];
        Notifier::send((int)$request['farmer_id'], 'Emergency request update', $texts[$status] ?? '', url('emergency/index'), $status === 'rejected' ? 'danger' : 'success');
        $this->success(t('Emergency request updated.'));
    }
}
