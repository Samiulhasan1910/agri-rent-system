<?php
declare(strict_types=1);

/** Authentication state: session login, remember-me cookie and idle timeout. */
final class Auth
{
    private const REMEMBER_COOKIE = 'agrirent_remember';
    private static ?array $user = null;
    private static bool $resolved = false;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = (int)Session::get('uid', 0);
        if (!$id) {
            $id = self::loginFromRememberCookie();
        }
        if (!$id) {
            return null;
        }

        $user = (new User())->find($id);
        if (!$user || $user['status'] !== 'active') {
            self::logout();
            return null;
        }

        $timeout = (int)setting('session_timeout_minutes', 60) * 60;
        $last = (int)Session::get('last_activity', time());
        if ($timeout > 0 && time() - $last > $timeout && empty($_COOKIE[self::REMEMBER_COOKIE])) {
            self::logout();
            Session::flash('warning', t('Your session expired. Please log in again.'));
            return null;
        }
        Session::set('last_activity', time());
        self::$user = $user;
        return $user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function is(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('uid', (int)$user['id']);
        Session::set('last_activity', time());
        Session::set('lang', $user['language'] ?? 'en');
        Session::forget('2fa_uid');
        if ($remember) {
            self::issueRememberToken((int)$user['id']);
        }
        self::$user = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        if (!empty(Session::get('uid'))) {
            (new User())->clearRememberToken((int)Session::get('uid'));
        }
        self::forgetCookie();
        $lang = Session::get('lang');
        Session::destroy();
        if ($lang) {
            Session::set('lang', $lang);
        }
        self::$user = null;
        self::$resolved = true;
    }

    /** Re-read the current user after profile changes. */
    public static function refresh(): void
    {
        self::$resolved = false;
        self::$user = null;
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $days = (int)config('security.remember_days', 30);
        (new User())->storeRememberToken($userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', time() + $days * 86400));
        setcookie(self::REMEMBER_COOKIE, $selector . ':' . $validator, [
            'expires' => time() + $days * 86400,
            'path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }

    private static function loginFromRememberCookie(): int
    {
        $cookie = (string)($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
            return 0;
        }
        $users = new User();
        $user = $users->findByRememberSelector($m[1]);
        if (!$user || !hash_equals((string)$user['remember_hash'], hash('sha256', $m[2]))
            || strtotime((string)$user['remember_expires']) < time() || $user['status'] !== 'active') {
            self::forgetCookie();
            return 0;
        }
        Session::regenerate();
        Session::set('uid', (int)$user['id']);
        Session::set('last_activity', time());
        Session::set('lang', $user['language']);
        self::issueRememberToken((int)$user['id']); // rotate
        return (int)$user['id'];
    }

    private static function forgetCookie(): void
    {
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }
}
