<?php
declare(strict_types=1);

class Delivery extends Model
{
    public const NEXT = [
        'pending' => ['assigned'],
        'assigned' => ['in_progress', 'delivered'],
        'in_progress' => ['delivered'],
        'delivered' => ['returned'],
    ];
    public const PROGRESS = ['pending' => 0, 'assigned' => 25, 'in_progress' => 60, 'delivered' => 100, 'returned' => 100, 'cancelled' => 0];

    private const SELECT = 'SELECT d.*, b.booking_code, b.status booking_status, b.start_date, b.end_date, b.farmer_id,
            e.name equipment_name, e.image, c.icon category_icon, u.name farmer_name, u.phone farmer_phone, u.email farmer_email,
            t.name team_name, t.leader_name team_leader, t.phone team_phone, t.email team_email, t.vehicle team_vehicle
        FROM deliveries d
        JOIN bookings b ON b.id=d.booking_id
        JOIN equipment e ON e.id=b.equipment_id
        JOIN categories c ON c.id=e.category_id
        JOIN users u ON u.id=b.farmer_id
        LEFT JOIN delivery_teams t ON t.id=d.team_id';

    public function paginateList(array $f, int $page, int $per = 10): array
    {
        $where = ["d.status<>'cancelled'"];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(b.booking_code LIKE ? OR u.name LIKE ? OR e.name LIKE ? OR d.address LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status'])) {
            $where[] = 'd.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['team'])) {
            $where[] = 'd.team_id=?';
            $params[] = (int)$f['team'];
        }
        if (!empty($f['date'])) {
            $where[] = 'd.scheduled_date=?';
            $params[] = $f['date'];
        }
        $sqlWhere = implode(' AND ', $where);
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM deliveries d JOIN bookings b ON b.id=d.booking_id JOIN equipment e ON e.id=b.equipment_id JOIN users u ON u.id=b.farmer_id WHERE {$sqlWhere}", $params), $page, $per);
        $rows = $this->all(self::SELECT . " WHERE {$sqlWhere} ORDER BY FIELD(d.status,'pending','assigned','in_progress','delivered','returned'), COALESCE(d.scheduled_date,b.start_date) ASC LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE d.id=?', [$id]);
    }

    public function forBooking(int $bookingId): ?array
    {
        return $this->one(self::SELECT . ' WHERE d.booking_id=?', [$bookingId]);
    }

    public function updates(int $deliveryId): array
    {
        return $this->all('SELECT du.*, u.name updated_by_name FROM delivery_updates du LEFT JOIN users u ON u.id=du.updated_by WHERE du.delivery_id=? ORDER BY du.id', [$deliveryId]);
    }

    public function assign(int $id, int $teamId, string $date, string $time, int $userId): array
    {
        return $this->transaction(function () use ($id, $teamId, $date, $time, $userId): array {
            $d = $this->one('SELECT d.*, b.status booking_status FROM deliveries d JOIN bookings b ON b.id=d.booking_id WHERE d.id=? FOR UPDATE', [$id]);
            if (!$d || in_array($d['status'], ['delivered', 'returned', 'cancelled'], true)) {
                throw new DomainException(t('This delivery can no longer be reassigned.'));
            }
            if (in_array($d['booking_status'], ['cancelled', 'rejected'], true)) {
                throw new DomainException(t('This booking was cancelled.'));
            }
            $status = $d['status'] === 'pending' ? 'assigned' : $d['status'];
            $this->run('UPDATE deliveries SET team_id=?, scheduled_date=?, estimated_time=?, status=?, progress=GREATEST(progress,?) WHERE id=?',
                [$teamId, $date, $time !== '' ? $time : null, $status, self::PROGRESS[$status], $id]);
            $team = (string)$this->value('SELECT name FROM delivery_teams WHERE id=?', [$teamId]);
            $this->insert('delivery_updates', ['delivery_id' => $id, 'status' => $status, 'note' => t('Assigned to :team for :date', ['team' => $team, 'date' => fmt_date($date)]), 'updated_by' => $userId]);
            return $this->find($id);
        });
    }

    /** Update progress and keep the booking status in sync (delivered / returned). */
    public function setStatus(int $id, string $status, string $note, int $userId): array
    {
        return $this->transaction(function () use ($id, $status, $note, $userId): array {
            $d = $this->one('SELECT d.*, b.status booking_status FROM deliveries d JOIN bookings b ON b.id=d.booking_id WHERE d.id=? FOR UPDATE', [$id]);
            if (!$d) {
                throw new DomainException(t('Delivery not found.'));
            }
            if (!in_array($status, self::NEXT[$d['status']] ?? [], true)) {
                throw new DomainException(t('Delivery cannot move from :from to :to.', ['from' => status_meta($d['status'])[0], 'to' => status_meta($status)[0]]));
            }
            if (in_array($status, ['in_progress', 'delivered'], true) && $d['booking_status'] !== 'confirmed') {
                throw new DomainException(t('The booking must be confirmed before it can be delivered.'));
            }
            if (!$d['team_id'] && $status !== 'assigned') {
                throw new DomainException(t('Assign a delivery team first.'));
            }
            $this->run("UPDATE deliveries SET status=?, progress=?, delivered_at=IF(?='delivered',NOW(),delivered_at), returned_at=IF(?='returned',NOW(),returned_at) WHERE id=?",
                [$status, self::PROGRESS[$status], $status, $status, $id]);
            $this->insert('delivery_updates', ['delivery_id' => $id, 'status' => $status, 'note' => $note !== '' ? mb_substr($note, 0, 255) : null, 'updated_by' => $userId]);
            if ($status === 'delivered') {
                $this->run("UPDATE bookings SET status='delivered' WHERE id=? AND status='confirmed'", [$d['booking_id']]);
            }
            if ($status === 'returned') {
                $this->run("UPDATE bookings SET status='returned' WHERE id=? AND status='delivered'", [$d['booking_id']]);
            }
            return $this->find($id);
        });
    }

    public function counts(): array
    {
        $counts = array_fill_keys(['pending', 'assigned', 'in_progress', 'delivered', 'returned'], 0);
        foreach ($this->all("SELECT status, COUNT(*) n FROM deliveries WHERE status<>'cancelled' GROUP BY status") as $r) {
            $counts[$r['status']] = (int)$r['n'];
        }
        return $counts;
    }

    public function teams(bool $activeOnly = false): array
    {
        return $this->all("SELECT t.*, (SELECT COUNT(*) FROM deliveries d WHERE d.team_id=t.id AND d.status IN ('assigned','in_progress')) active_jobs
            FROM delivery_teams t" . ($activeOnly ? " WHERE t.status='active'" : '') . ' ORDER BY t.name');
    }

    public function team(int $id): ?array
    {
        return $this->one('SELECT * FROM delivery_teams WHERE id=?', [$id]);
    }

    public function saveTeam(array $data, int $id = 0): int
    {
        if ($id) {
            $this->update('delivery_teams', $id, $data);
            return $id;
        }
        return $this->insert('delivery_teams', $data);
    }

    public function teamNameExists(string $name, int $exceptId = 0): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM delivery_teams WHERE name=? AND id<>?', [$name, $exceptId]);
    }
}
