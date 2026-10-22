<?php
declare(strict_types=1);

class Emergency extends Model
{
    /** Bangladesh districts used when GPS is unavailable. */
    public const DISTRICTS = [
        'Dhaka' => [23.8103, 90.4125], 'Gazipur' => [23.9999, 90.4203], 'Mymensingh' => [24.7471, 90.4203],
        'Tangail' => [24.2513, 89.9167], 'Bogura' => [24.8465, 89.3776], 'Rajshahi' => [24.3745, 88.6042],
        'Rangpur' => [25.7439, 89.2752], 'Dinajpur' => [25.6217, 88.6355], 'Khulna' => [22.8456, 89.5403],
        'Satkhira' => [22.7185, 89.0705], 'Jashore' => [23.1664, 89.2081], 'Kushtia' => [23.9013, 89.1205],
        'Barishal' => [22.7010, 90.3535], 'Cumilla' => [23.4607, 91.1809], 'Sylhet' => [24.8949, 91.8687],
        'Chattogram' => [22.3569, 91.7832], 'Natore' => [24.4206, 88.9846], 'Pabna' => [24.0064, 89.2372],
        'Faridpur' => [23.6070, 89.8429], 'Jamalpur' => [24.9375, 89.9370], 'Sirajganj' => [24.4534, 89.7007],
        'Noakhali' => [22.8696, 91.0995],
    ];

    private const SELECT = 'SELECT r.*, c.name category_name, c.icon category_icon, e.name equipment_name, e.location equipment_location,
            e.owner_phone, u.name farmer_name, u.phone farmer_phone, h.name handler_name
        FROM emergency_requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.farmer_id
        LEFT JOIN equipment e ON e.id=r.equipment_id LEFT JOIN users h ON h.id=r.handled_by';

    public function create(array $data): array
    {
        return $this->transaction(function () use ($data): array {
            $id = $this->insert('emergency_requests', $data);
            $code = 'EMR-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            $this->run('UPDATE emergency_requests SET request_code=? WHERE id=?', [$code, $id]);
            return ['id' => $id, 'code' => $code];
        });
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE r.id=?', [$id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->one(self::SELECT . ' WHERE r.client_uuid=?', [$uuid]);
    }

    public function forFarmer(int $farmerId): array
    {
        return $this->all(self::SELECT . ' WHERE r.farmer_id=? ORDER BY r.id DESC LIMIT 20', [$farmerId]);
    }

    public function listAll(string $status = ''): array
    {
        $params = [];
        $sql = self::SELECT;
        if ($status !== '') {
            $sql .= ' WHERE r.status=?';
            $params[] = $status;
        }
        return $this->all($sql . " ORDER BY FIELD(r.status,'pending','approved','completed','rejected'), r.needed_by ASC LIMIT 100", $params);
    }

    public function setStatus(int $id, string $status, int $handlerId): int
    {
        $allowed = ['pending' => ['approved', 'rejected'], 'approved' => ['completed', 'rejected']];
        $current = (string)$this->value('SELECT status FROM emergency_requests WHERE id=?', [$id]);
        if (!in_array($status, $allowed[$current] ?? [], true)) {
            throw new DomainException(t('This emergency request can no longer be changed.'));
        }
        return $this->run('UPDATE emergency_requests SET status=?, handled_by=? WHERE id=?', [$status, $handlerId, $id])->rowCount();
    }

    public function countPending(): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM emergency_requests WHERE status='pending'");
    }
}
