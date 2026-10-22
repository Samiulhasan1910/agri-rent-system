<?php
declare(strict_types=1);

class Maintenance extends Model
{
    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    public function schedule(array $data): int
    {
        return $this->transaction(function () use ($data): int {
            $equipment = $this->one('SELECT * FROM equipment WHERE id=? FOR UPDATE', [$data['equipment_id']]);
            if (!$equipment || $equipment['status'] === 'inactive') {
                throw new DomainException(t('Maintenance scheduling failed.'));
            }
            $overlap = $this->value("SELECT COUNT(*) FROM maintenance WHERE equipment_id=? AND status IN ('scheduled','in_progress') AND scheduled_date<=? AND end_date>=?",
                [$data['equipment_id'], $data['end_date'], $data['scheduled_date']]);
            if ($overlap) {
                throw new DomainException(t('Maintenance is already scheduled for this equipment in that period.'));
            }
            $id = $this->insert('maintenance', $data);
            if ($data['status'] === 'in_progress') {
                $this->run("UPDATE equipment SET status='maintenance' WHERE id=?", [$data['equipment_id']]);
            }
            return $id;
        });
    }

    /** Bookings that clash with a maintenance window (shown as a warning to staff). */
    public function clashingBookings(int $equipmentId, string $from, string $to): array
    {
        return $this->all("SELECT b.booking_code, b.start_date, b.end_date, u.name farmer_name FROM bookings b JOIN users u ON u.id=b.farmer_id
            WHERE b.equipment_id=? AND b.status IN ('pending','confirmed','delivered') AND b.start_date<=? AND b.end_date>=?", [$equipmentId, $to, $from]);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT m.*, e.name equipment_name FROM maintenance m JOIN equipment e ON e.id=m.equipment_id WHERE m.id=?', [$id]);
    }

    public function listAll(array $f): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['status'])) {
            $where[] = 'm.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['equipment'])) {
            $where[] = 'm.equipment_id=?';
            $params[] = (int)$f['equipment'];
        }
        return $this->all('SELECT m.*, e.name equipment_name, e.image, c.icon category_icon, u.name created_by_name
            FROM maintenance m JOIN equipment e ON e.id=m.equipment_id JOIN categories c ON c.id=e.category_id JOIN users u ON u.id=m.created_by
            WHERE ' . implode(' AND ', $where) . " ORDER BY FIELD(m.status,'in_progress','scheduled','completed','cancelled'), m.scheduled_date DESC LIMIT 200", $params);
    }

    public function forEquipment(int $equipmentId): array
    {
        return $this->all('SELECT m.*, u.name created_by_name FROM maintenance m JOIN users u ON u.id=m.created_by WHERE m.equipment_id=? ORDER BY m.scheduled_date DESC', [$equipmentId]);
    }

    public function setStatus(int $id, string $status): array
    {
        return $this->transaction(function () use ($id, $status): array {
            $row = $this->one('SELECT * FROM maintenance WHERE id=? FOR UPDATE', [$id]);
            if (!$row) {
                throw new DomainException(t('Maintenance record not found.'));
            }
            $allowed = ['scheduled' => ['in_progress', 'completed', 'cancelled'], 'in_progress' => ['completed', 'cancelled']];
            if (!in_array($status, $allowed[$row['status']] ?? [], true)) {
                throw new DomainException(t('This maintenance record can no longer be changed.'));
            }
            $this->run('UPDATE maintenance SET status=?, completed_at=IF(?=\'completed\',NOW(),completed_at) WHERE id=?', [$status, $status, $id]);
            $equipmentId = (int)$row['equipment_id'];
            if ($status === 'in_progress') {
                $this->run("UPDATE equipment SET status='maintenance' WHERE id=?", [$equipmentId]);
            } elseif ($status === 'completed') {
                Health::markServiced($equipmentId);
            } elseif ($status === 'cancelled') {
                $stillActive = (int)$this->value("SELECT COUNT(*) FROM maintenance WHERE equipment_id=? AND status='in_progress' AND id<>?", [$equipmentId, $id]);
                if (!$stillActive) {
                    $this->run("UPDATE equipment SET status='available' WHERE id=? AND status='maintenance'", [$equipmentId]);
                }
            }
            Health::evaluate($equipmentId);
            return $row;
        });
    }

    /** Starts maintenance whose scheduled date has arrived (keeps statuses current without cron). */
    public function startDue(): void
    {
        $due = $this->all("SELECT id FROM maintenance WHERE status='scheduled' AND scheduled_date<=CURDATE()");
        foreach ($due as $row) {
            try {
                $this->setStatus((int)$row['id'], 'in_progress');
            } catch (Throwable $e) {
                error_log('Maintenance auto-start failed: ' . $e->getMessage());
            }
        }
    }

    public function alerts(string $status = 'open', int $limit = 50): array
    {
        return $this->all("SELECT a.*, e.name equipment_name, e.health_status FROM maintenance_alerts a JOIN equipment e ON e.id=a.equipment_id
            WHERE a.status=? ORDER BY a.id DESC LIMIT " . (int)$limit, [$status]);
    }

    public function resolveAlert(int $id): int
    {
        return $this->run("UPDATE maintenance_alerts SET status='resolved', resolved_at=NOW() WHERE id=? AND status='open'", [$id])->rowCount();
    }

    public function alertEquipment(int $id): ?int
    {
        $v = $this->value('SELECT equipment_id FROM maintenance_alerts WHERE id=?', [$id]);
        return $v === null ? null : (int)$v;
    }

    public function usageLogs(?int $equipmentId = null, int $limit = 30): array
    {
        $params = [];
        $sql = 'SELECT l.*, e.name equipment_name, b.booking_code, u.name logged_by_name FROM usage_logs l JOIN equipment e ON e.id=l.equipment_id
            LEFT JOIN bookings b ON b.id=l.booking_id LEFT JOIN users u ON u.id=l.logged_by';
        if ($equipmentId) {
            $sql .= ' WHERE l.equipment_id=?';
            $params[] = $equipmentId;
        }
        return $this->all($sql . ' ORDER BY l.id DESC LIMIT ' . (int)$limit, $params);
    }

    public function counts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($this->all('SELECT status, COUNT(*) n FROM maintenance GROUP BY status') as $r) {
            $counts[$r['status']] = (int)$r['n'];
        }
        return $counts;
    }
}
