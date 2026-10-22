<?php
declare(strict_types=1);

/** Farmer <-> staff chat. */
class Message extends Model
{
    /** Contacts: farmers chat with staff; staff chat with farmers (and each other). */
    public function contacts(array $user, string $q = '', string $tab = 'active'): array
    {
        $params = [$user['id'], $user['id'], $user['id'], $user['id']];
        $roleFilter = $user['role'] === 'farmer' ? "u.role IN ('employee','admin')" : "u.role IN ('farmer','employee','admin')";
        $sql = "SELECT u.id, u.name, u.role, u.profile_photo,
                (SELECT m.message FROM messages m WHERE (m.sender_id=u.id AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=u.id) ORDER BY m.id DESC LIMIT 1) last_message,
                (SELECT MAX(m.created_at) FROM messages m WHERE (m.sender_id=u.id AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=u.id)) last_at,
                (SELECT COUNT(*) FROM messages m WHERE m.sender_id=u.id AND m.receiver_id=? AND m.is_read=0) unread
            FROM users u WHERE u.id<>? AND u.status='active' AND {$roleFilter}";
        $params[] = $user['id'];
        $params[] = $user['id'];
        if ($q !== '') {
            $sql .= ' AND u.name LIKE ?';
            $params[] = "%{$q}%";
        }
        $rows = $this->all($sql . ' ORDER BY last_at IS NULL, last_at DESC, u.name LIMIT 100', $params);
        if ($user['role'] !== 'farmer' && $tab === 'active') {
            $rows = array_values(array_filter($rows, static fn($r) => $r['last_at'] !== null));
        } elseif ($tab === 'unread') {
            $rows = array_values(array_filter($rows, static fn($r) => (int)$r['unread'] > 0));
        }
        return $rows;
    }

    public function canChat(array $user, int $otherId): ?array
    {
        $other = $this->one("SELECT id,name,role,profile_photo,status FROM users WHERE id=? AND status='active'", [$otherId]);
        if (!$other || $other['id'] == $user['id']) {
            return null;
        }
        if ($user['role'] === 'farmer' && $other['role'] === 'farmer') {
            return null;
        }
        return $other;
    }

    public function conversation(int $me, int $other, int $afterId = 0): array
    {
        return $this->all('SELECT m.*, s.name sender_name FROM messages m JOIN users s ON s.id=m.sender_id
            WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)) AND m.id>? ORDER BY m.id ASC LIMIT 300',
            [$me, $other, $other, $me, $afterId]);
    }

    public function send(int $from, int $to, string $text): array
    {
        $id = $this->insert('messages', ['sender_id' => $from, 'receiver_id' => $to, 'message' => $text]);
        return $this->one('SELECT m.*, s.name sender_name FROM messages m JOIN users s ON s.id=m.sender_id WHERE m.id=?', [$id]);
    }

    public function markRead(int $me, int $other): void
    {
        $this->run('UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0', [$other, $me]);
    }

    /** True when the receiver still has older unread messages from this sender (avoid duplicate alerts). */
    public function hasEarlierUnread(int $from, int $to, int $exceptId): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0 AND id<>?', [$from, $to, $exceptId]);
    }

    public function unreadCount(int $userId): int
    {
        return (int)$this->value('SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0', [$userId]);
    }
}
