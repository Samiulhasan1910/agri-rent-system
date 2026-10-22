<?php
declare(strict_types=1);

/** One-time tokens: email verification, password reset and login OTP. Only hashes are stored. */
class Token extends Model
{
    /** @return array{token: string, code: string} */
    public function issue(int $userId, string $type, int $linkSeconds, int $codeSeconds): array
    {
        $this->run('UPDATE user_tokens SET used_at=NOW() WHERE user_id=? AND type=? AND used_at IS NULL', [$userId, $type]);
        $token = bin2hex(random_bytes(32));
        $code = random_digits(6);
        $this->insert('user_tokens', [
            'user_id' => $userId,
            'type' => $type,
            'token_hash' => hash('sha256', $token),
            'code_hash' => $this->codeHash($userId, $code),
            'expires_at' => date('Y-m-d H:i:s', time() + $linkSeconds),
            'code_expires_at' => date('Y-m-d H:i:s', time() + $codeSeconds),
        ]);
        return ['token' => $token, 'code' => $code];
    }

    /** Returns the token row when the link is valid and unexpired. */
    public function findByLink(string $type, string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->one('SELECT * FROM user_tokens WHERE type=? AND token_hash=? AND used_at IS NULL AND expires_at > NOW() LIMIT 1', [$type, hash('sha256', $token)]);
    }

    /** @return array{ok: bool, reason: string, row: ?array} reason: none|expired|invalid|locked */
    public function checkCode(int $userId, string $type, string $code): array
    {
        $row = $this->one('SELECT * FROM user_tokens WHERE user_id=? AND type=? AND used_at IS NULL ORDER BY id DESC LIMIT 1', [$userId, $type]);
        if (!$row) {
            return ['ok' => false, 'reason' => 'none', 'row' => null];
        }
        if ((int)$row['attempts'] >= (int)config('security.otp_max_attempts', 5)) {
            return ['ok' => false, 'reason' => 'locked', 'row' => $row];
        }
        if (strtotime($row['code_expires_at']) < time()) {
            return ['ok' => false, 'reason' => 'expired', 'row' => $row];
        }
        if (!preg_match('/^\d{6}$/', $code) || !hash_equals($row['code_hash'], $this->codeHash($userId, $code))) {
            $this->run('UPDATE user_tokens SET attempts=attempts+1 WHERE id=?', [$row['id']]);
            return ['ok' => false, 'reason' => 'invalid', 'row' => $row];
        }
        return ['ok' => true, 'reason' => '', 'row' => $row];
    }

    public function consume(int $id): void
    {
        $this->run('UPDATE user_tokens SET used_at=NOW() WHERE id=?', [$id]);
    }

    public function latest(int $userId, string $type): ?array
    {
        return $this->one('SELECT * FROM user_tokens WHERE user_id=? AND type=? ORDER BY id DESC LIMIT 1', [$userId, $type]);
    }

    private function codeHash(int $userId, string $code): string
    {
        return hash('sha256', $userId . '|' . $code . '|agrirent');
    }
}
