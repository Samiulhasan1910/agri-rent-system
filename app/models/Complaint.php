<?php
declare(strict_types=1);

class Complaint extends Model
{
    public const CATEGORIES = ['Equipment Issue', 'Delivery Issue', 'Payment Issue', 'Service Issue', 'Account Issue', 'Other'];
    public const STATUSES = ['pending', 'in_review', 'resolved', 'closed'];

    private const SELECT = 'SELECT c.*, u.name user_name, u.role user_role, u.email user_email, u.phone user_phone,
            b.booking_code, h.name handler_name
        FROM complaints c JOIN users u ON u.id=c.user_id
        LEFT JOIN bookings b ON b.id=c.booking_id LEFT JOIN users h ON h.id=c.handled_by';

    /** Creates the complaint and its unique ticket ID inside one transaction. */
    public function create(array $data): array
    {
        return $this->transaction(function () use ($data): array {
            $id = $this->insert('complaints', $data);
            $ticket = 'CMP-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            if ($this->run('UPDATE complaints SET ticket_no=? WHERE id=?', [$ticket, $id])->rowCount() !== 1) {
                throw new RuntimeException(t('Ticket ID generation failed.'));
            }
            return ['id' => $id, 'ticket_no' => $ticket];
        });
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE c.id=?', [$id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->one('SELECT id, ticket_no FROM complaints WHERE client_uuid=?', [$uuid]);
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        return $this->all(self::SELECT . ' WHERE c.user_id=? ORDER BY c.id DESC LIMIT ' . (int)$limit, [$userId]);
    }

    public function paginateList(array $f, int $page, int $per = 10): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(c.ticket_no LIKE ? OR c.subject LIKE ? OR u.name LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status'])) {
            $where[] = 'c.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['category'])) {
            $where[] = 'c.category=?';
            $params[] = $f['category'];
        }
        if (!empty($f['priority'])) {
            $where[] = 'c.priority=?';
            $params[] = $f['priority'];
        }
        $sqlWhere = implode(' AND ', $where);
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM complaints c JOIN users u ON u.id=c.user_id WHERE {$sqlWhere}", $params), $page, $per);
        $rows = $this->all(self::SELECT . " WHERE {$sqlWhere} ORDER BY FIELD(c.status,'pending','in_review','resolved','closed'), FIELD(c.priority,'high','medium','low'), c.id DESC LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function updateStatus(int $id, string $status, string $response, int $handlerId): int
    {
        return $this->run('UPDATE complaints SET status=?, admin_response=?, handled_by=? WHERE id=?', [$status, $response !== '' ? $response : null, $handlerId, $id])->rowCount();
    }

    public function countOpen(?int $userId = null): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM complaints WHERE status IN ('pending','in_review')" . ($userId ? ' AND user_id=?' : ''), $userId ? [$userId] : []);
    }
}
