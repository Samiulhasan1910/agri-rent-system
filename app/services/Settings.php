<?php
declare(strict_types=1);

/** Key/value system settings stored in the `settings` table (cached per request). */
final class Settings
{
    private static ?array $cache = null;

    public const DEFAULTS = [
        'platform_name' => 'AgriRent', 'support_email' => 'support@agrirent.com', 'support_phone' => '16247',
        'default_language' => 'en', 'timezone' => 'Asia/Dhaka', 'delivery_charge' => '300', 'emergency_radius_km' => '100',
        'enable_2fa_all' => '0', 'session_timeout_minutes' => '60', 'max_login_attempts' => '5', 'lockout_minutes' => '15',
        'suspicious_activity_alert' => '1', 'season_mode' => 'auto', 'current_season' => 'normal',
        'harvest_months' => '4,5,11,12', 'off_season_months' => '1,2,7', 'harvest_increase_percent' => '20',
        'off_season_discount_percent' => '10', 'min_rental_price' => '100', 'email_notifications' => '1',
        'payment_failure_alert' => '1', 'delivery_completion_alert' => '1', 'complaint_ticket_alert' => '1',
        'maintenance_alert' => '1', 'method_bkash' => '1', 'method_nagad' => '1', 'method_card' => '1', 'method_cash' => '1',
        'default_usage_threshold' => '250', 'block_needs_maintenance' => '1',
    ];

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::DEFAULTS;
            try {
                foreach (Database::connection()->query('SELECT `key`,`value` FROM settings')->fetchAll() as $row) {
                    self::$cache[$row['key']] = $row['value'];
                }
            } catch (PDOException $e) {
                if (PHP_SAPI !== 'cli' && !str_contains($e->getMessage(), '1146')) {
                    throw $e;
                }
            }
        }
        return self::$cache;
    }

    public static function get(string $key, $default = '')
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function enabled(string $key): bool
    {
        return (string)self::get($key, '0') === '1';
    }

    /** Saves values and returns the list of [key, old, new] that changed. */
    public static function save(array $values): array
    {
        $current = self::all();
        $changes = [];
        $stmt = Database::connection()->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
        foreach ($values as $key => $value) {
            $value = (string)$value;
            if (($current[$key] ?? null) !== $value) {
                $changes[] = [$key, $current[$key] ?? null, $value];
            }
            $stmt->execute([$key, $value]);
            self::$cache[$key] = $value;
        }
        return $changes;
    }
}
