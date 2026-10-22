<?php
declare(strict_types=1);

/**
 * Replays actions queued while offline. Each item carries a client UUID so retries
 * never create duplicates (no data loss, no double bookings).
 */
class SyncController extends Controller
{
    public function push(): void
    {
        $user = $this->auth();
        $this->requirePost();
        $items = json_decode((string)($_POST['items'] ?? '[]'), true);
        if (!is_array($items) || count($items) > 50) {
            $this->fail(t('Synchronization failed. Please retry.'));
        }
        $log = new SyncLog();
        $results = [];
        foreach ($items as $item) {
            $uuid = strtolower((string)($item['uuid'] ?? ''));
            $type = (string)($item['type'] ?? '');
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
            if (!is_uuid($uuid)) {
                $results[] = ['uuid' => $uuid, 'ok' => false, 'message' => t('Invalid offline item.')];
                continue;
            }
            if ($previous = $log->find($uuid)) {
                $results[] = ['uuid' => $uuid, 'ok' => true, 'message' => $previous['result'], 'duplicate' => true];
                continue;
            }
            try {
                [$ok, $message] = $this->apply($user, $type, $payload, $uuid);
            } catch (Throwable $e) {
                error_log('Sync error: ' . $e->getMessage());
                [$ok, $message] = [false, t('Synchronization failed. Please retry.')];
            }
            if ($ok) {
                $log->record($uuid, (int)$user['id'], $type, $message);
            }
            $results[] = ['uuid' => $uuid, 'ok' => $ok, 'message' => $message];
        }
        $this->json(['ok' => true, 'results' => $results, 'synced_at' => date('c')]);
    }

    private function apply(array $user, string $type, array $p, string $uuid): array
    {
        switch ($type) {
            case 'wishlist':
                if ($user['role'] !== 'farmer') {
                    return [false, t('You are not authorized to access this page.')];
                }
                $wishlist = new Wishlist();
                $equipmentId = (int)($p['equipment_id'] ?? 0);
                if (!empty($p['remove'])) {
                    $wishlist->remove((int)$user['id'], $equipmentId);
                    return [true, t('Removed from wishlist.')];
                }
                if (!$wishlist->has((int)$user['id'], $equipmentId) && (new Equipment())->find($equipmentId)) {
                    $wishlist->add((int)$user['id'], $equipmentId);
                }
                return [true, t('Saved to wishlist.')];

            case 'complaint':
                $category = (string)($p['category'] ?? '');
                $description = trim((string)($p['description'] ?? ''));
                $subject = trim((string)($p['subject'] ?? ''));
                if (!in_array($category, Complaint::CATEGORIES, true)) {
                    return [false, t('Complaint category selection failed.')];
                }
                if ($description === '' || $subject === '') {
                    return [false, t('Description cannot be empty.')];
                }
                $complaints = new Complaint();
                if ($existing = $complaints->findByUuid($uuid)) {
                    return [true, t('Ticket ID: :ticket', ['ticket' => $existing['ticket_no']])];
                }
                $created = $complaints->create([
                    'user_id' => (int)$user['id'], 'category' => $category, 'subject' => mb_substr($subject, 0, 180),
                    'priority' => in_array($p['priority'] ?? '', ['low', 'medium', 'high'], true) ? $p['priority'] : 'medium',
                    'description' => mb_substr($description, 0, 3000), 'client_uuid' => $uuid,
                ]);
                Notifier::staff('New complaint', t(':ticket: :subject (:category) from :name.', ['ticket' => $created['ticket_no'], 'subject' => $subject, 'category' => $category, 'name' => $user['name']]), url('complaint/show', ['id' => $created['id']]));
                return [true, t('Complaint submitted. Ticket ID: :ticket', ['ticket' => $created['ticket_no']])];

            case 'booking':
                if ($user['role'] !== 'farmer') {
                    return [false, t('You are not authorized to access this page.')];
                }
                if ($user['verification_status'] !== 'approved') {
                    return [false, t('Account verification required before booking.')];
                }
                $bookings = new Booking();
                if ($existing = $bookings->findByUuid($uuid)) {
                    return [true, t('Booking :code created.', ['code' => $existing['booking_code']])];
                }
                try {
                    $id = $bookings->create((int)$user['id'], (int)($p['equipment_id'] ?? 0), (string)($p['start_date'] ?? ''), (string)($p['end_date'] ?? ''),
                        trim((string)($p['delivery_address'] ?? $user['address'])) ?: $user['address'], (string)($p['notes'] ?? ''), $uuid);
                } catch (InvalidArgumentException | DomainException $e) {
                    return [false, $e->getMessage()];
                }
                $b = $bookings->find($id);
                Notifier::staff('New rental request', t(':code for :equipment (:from to :to) is waiting for approval.', ['code' => $b['booking_code'], 'equipment' => $b['equipment_name'], 'from' => fmt_date($b['start_date']), 'to' => fmt_date($b['end_date'])]), url('rental/index'));
                Notifier::send((int)$user['id'], 'Booking request synced', t('Your offline booking request :code was submitted. Please complete payment.', ['code' => $b['booking_code']]), url('payment/checkout', ['booking' => $id]), 'success');
                return [true, t('Booking :code created. Please complete payment.', ['code' => $b['booking_code']])];

            case 'emergency':
                if ($user['role'] !== 'farmer') {
                    return [false, t('You are not authorized to access this page.')];
                }
                $result = (new EmergencyController())->createRequest($user, $p + ['client_uuid' => $uuid]);
                return [$result['ok'], $result['message']];
        }
        return [false, t('Unknown offline action.')];
    }
}
