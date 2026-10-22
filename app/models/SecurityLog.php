<?php
declare(strict_types=1);

/** Audit trail for authentication & account security (fraud detection, exports). */
class SecurityLog extends Model
{
    public function log(string $event, ?int $userId, ?string $email, string $details = ''): void
    {
        try {
            $this->insert('security_logs', [
                'user_id' => $userId,
                'email' => $email ? mb_substr($email, 0, 160) : null,
                'event' => $event,
                'ip' => client_ip(),
                'user_agent' => user_agent(),
                'details' => mb_substr($details, 0, 500),
            ]);
        } catch (Throwable $e) {
            error_log('Security log failed: ' . $e->getMessage());
        }
    }

    /** Failed logins from this IP in the last N minutes (credential-stuffing detection). */
    public function recentFailuresFromIp(int $minutes = 15): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM security_logs WHERE event='login_failed' AND ip=? AND created_at > (NOW() - INTERVAL ? MINUTE)", [client_ip(), $minutes]);
    }

    public function hasSeenDevice(int $userId): bool
    {
        return (bool)$this->value("SELECT COUNT(*) FROM security_logs WHERE user_id=? AND event='login_success' AND ip=? AND user_agent=?", [$userId, client_ip(), user_agent()]);
    }

    public function hasLoggedInBefore(int $userId): bool
    {
        return (bool)$this->value("SELECT COUNT(*) FROM security_logs WHERE user_id=? AND event='login_success'", [$userId]);
    }

    public function paginateList(array $f, int $page, int $per = 15): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['event'])) {
            $where[] = 'l.event=?';
            $params[] = $f['event'];
        }
        if (!empty($f['q'])) {
            $where[] = '(l.email LIKE ? OR l.ip LIKE ? OR l.details LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        $sqlWhere = implode(' AND ', $where);
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM security_logs l WHERE {$sqlWhere}", $params), $page, $per);
        $rows = $this->all("SELECT l.*, u.name FROM security_logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$sqlWhere} ORDER BY l.id DESC LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function export(): array
    {
        return $this->all('SELECT l.created_at, l.event, l.email, u.name, l.ip, l.user_agent, l.details FROM security_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 5000');
    }

    public function events(): array
    {
        return $this->all('SELECT DISTINCT event FROM security_logs ORDER BY event');
    }
}
