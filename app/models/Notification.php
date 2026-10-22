<?php
declare(strict_types=1);

class Notification extends Model
{
    public function latest(int $userId, int $limit = 6): array
    {
        return $this->all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT ' . (int)$limit, [$userId]);
    }

    public function paginateList(int $userId, string $filter, int $page, int $per = 15): array
    {
        $where = 'user_id=?' . ($filter === 'unread' ? ' AND is_read=0' : '');
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM notifications WHERE {$where}", [$userId]), $page, $per);
        $rows = $this->all("SELECT * FROM notifications WHERE {$where} ORDER BY id DESC LIMIT {$meta['per']} OFFSET {$meta['offset']}", [$userId]);
        return [$rows, $meta];
    }

    public function unreadCount(int $userId): int
    {
        return (int)$this->value('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->run('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0', [$userId]);
    }

    public function markRead(int $id, int $userId): ?array
    {
        $row = $this->one('SELECT * FROM notifications WHERE id=? AND user_id=?', [$id, $userId]);
        if ($row) {
            $this->run('UPDATE notifications SET is_read=1 WHERE id=?', [$id]);
        }
        return $row;
    }

    public function remove(int $id, int $userId): int
    {
        return $this->run('DELETE FROM notifications WHERE id=? AND user_id=?', [$id, $userId])->rowCount();
    }
}
