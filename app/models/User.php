<?php
declare(strict_types=1);

class User extends Model
{
    public function find(int $id): ?array
    {
        return $this->one('SELECT * FROM users WHERE id=?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->one('SELECT * FROM users WHERE email=? LIMIT 1', [mb_strtolower(trim($email))]);
    }

    public function findByGoogle(string $googleId): ?array
    {
        return $this->one('SELECT * FROM users WHERE google_id=? LIMIT 1', [$googleId]);
    }

    public function findByRememberSelector(string $selector): ?array
    {
        return $this->one('SELECT * FROM users WHERE remember_selector=? LIMIT 1', [$selector]);
    }

    public function emailExists(string $email, int $exceptId = 0): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM users WHERE email=? AND id<>?', [mb_strtolower(trim($email)), $exceptId]);
    }

    public function create(array $data): int
    {
        $data['email'] = mb_strtolower(trim($data['email']));
        $id = $this->insert('users', $data);
        if (($data['role'] ?? '') === 'employee' && empty($data['employee_code'])) {
            $this->run('UPDATE users SET employee_code=? WHERE id=?', ['EM-' . date('Y') . '-' . str_pad((string)$id, 2, '0', STR_PAD_LEFT), $id]);
        }
        return $id;
    }

    public function updateProfile(int $id, array $data): void
    {
        $this->update('users', $id, $data);
    }

    public function setPassword(int $id, string $password): void
    {
        $this->run('UPDATE users SET password=?, failed_attempts=0, locked_until=NULL, remember_selector=NULL, remember_hash=NULL WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function markEmailVerified(int $id): void
    {
        $this->run("UPDATE users SET email_verified_at=COALESCE(email_verified_at,NOW()), status=IF(status='inactive','active',status) WHERE id=?", [$id]);
    }

    /** Increments failed attempts and locks the account when the limit is reached. Returns [attempts, lockedUntil]. */
    public function recordFailedLogin(int $id, int $maxAttempts, int $lockMinutes): array
    {
        $this->run('UPDATE users SET failed_attempts=failed_attempts+1 WHERE id=?', [$id]);
        $attempts = (int)$this->value('SELECT failed_attempts FROM users WHERE id=?', [$id]);
        $lockedUntil = null;
        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
            $this->run('UPDATE users SET locked_until=?, failed_attempts=0 WHERE id=?', [$lockedUntil, $id]);
        }
        return [$attempts, $lockedUntil];
    }

    public function recordLogin(int $id): void
    {
        $this->run('UPDATE users SET failed_attempts=0, locked_until=NULL, last_login_at=NOW(), last_login_ip=?, last_login_agent=? WHERE id=?', [client_ip(), user_agent(), $id]);
    }

    public function storeRememberToken(int $id, string $selector, string $hash, string $expires): void
    {
        $this->run('UPDATE users SET remember_selector=?, remember_hash=?, remember_expires=? WHERE id=?', [$selector, $hash, $expires, $id]);
    }

    public function clearRememberToken(int $id): void
    {
        $this->run('UPDATE users SET remember_selector=NULL, remember_hash=NULL, remember_expires=NULL WHERE id=?', [$id]);
    }

    public function setLanguage(int $id, string $lang): void
    {
        $this->run('UPDATE users SET language=? WHERE id=?', [$lang === 'bn' ? 'bn' : 'en', $id]);
    }

    public function setTwoFactor(int $id, bool $enabled): void
    {
        $this->run('UPDATE users SET two_factor_enabled=? WHERE id=?', [$enabled ? 1 : 0, $id]);
    }

    public function setPhoto(int $id, string $path): void
    {
        $this->run('UPDATE users SET profile_photo=? WHERE id=?', [$path, $id]);
    }

    /** New/updated document puts a rejected or pending account back in the verification queue. */
    public function setDocument(int $id, string $path): void
    {
        $this->run("UPDATE users SET verification_document=?, verification_status=IF(verification_status='approved','approved','pending'), verification_note=NULL WHERE id=?", [$path, $id]);
    }

    public function softDelete(int $id): void
    {
        $this->run("UPDATE users SET status='deleted', deleted_at=NOW(), two_factor_enabled=0, remember_selector=NULL, remember_hash=NULL, google_id=NULL WHERE id=?", [$id]);
        $this->run('DELETE FROM wishlist WHERE farmer_id=?', [$id]);
        $this->run('UPDATE user_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL', [$id]);
    }

    public function setStatus(int $id, string $status): int
    {
        return $this->run("UPDATE users SET status=? WHERE id=? AND role<>'admin'", [$status, $id])->rowCount();
    }

    public function setVerification(int $id, string $decision, int $by, ?string $note): int
    {
        return $this->run('UPDATE users SET verification_status=?, verification_note=?, verified_by=?, verified_at=NOW() WHERE id=?', [$decision, $note, $by, $id])->rowCount();
    }

    /** Accounts awaiting document verification (roles visible to the reviewer). */
    public function verificationQueue(array $roles, string $status = 'pending', string $q = ''): array
    {
        $params = $roles;
        $sql = "SELECT u.*, v.name verified_by_name FROM users u LEFT JOIN users v ON v.id=u.verified_by
                WHERE u.role IN ({$this->in($roles)}) AND u.status IN ('active','inactive')";
        if ($status !== 'all') {
            $sql .= ' AND u.verification_status=?';
            $params[] = $status;
        }
        if ($q !== '') {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        return $this->all($sql . " ORDER BY u.verification_status='pending' DESC, u.created_at DESC LIMIT 200", $params);
    }

    public function paginateList(array $f, int $page, int $per = 12): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['role'])) {
            $where[] = 'role=?';
            $params[] = $f['role'];
        }
        if (!empty($f['status'])) {
            $where[] = 'status=?';
            $params[] = $f['status'];
        }
        $sqlWhere = implode(' AND ', $where);
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM users WHERE {$sqlWhere}", $params), $page, $per);
        $rows = $this->all("SELECT * FROM users WHERE {$sqlWhere} ORDER BY created_at DESC LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function activeList(string $role = ''): array
    {
        $sql = "SELECT id,name,email,role,status FROM users WHERE status IN ('active','suspended') AND role<>'admin'";
        $params = [];
        if ($role !== '') {
            $sql .= ' AND role=?';
            $params[] = $role;
        }
        return $this->all($sql . ' ORDER BY name', $params);
    }

    public function countFarmers(): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM users WHERE role='farmer' AND status='active'");
    }

    public function countPendingVerification(array $roles): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM users WHERE role IN ({$this->in($roles)}) AND verification_status='pending' AND status='active'", $roles);
    }

    public function supportContact(): ?array
    {
        return $this->one("SELECT id,name FROM users WHERE role='employee' AND status='active' AND verification_status='approved' ORDER BY id LIMIT 1");
    }
}
