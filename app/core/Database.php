<?php
declare(strict_types=1);

/** Shared PDO connection (lazy). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // Keep MySQL NOW()/CURRENT_TIMESTAMP aligned with the PHP timezone.
            self::$pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
        }
        return self::$pdo;
    }
}
