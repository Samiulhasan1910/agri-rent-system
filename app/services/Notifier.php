<?php
declare(strict_types=1);

/** In-app notifications (with optional email copy). */
final class Notifier
{
    /** Returns false when the notification could not be stored. */
    public static function send(int $userId, string $title, string $message, ?string $link = null, string $type = 'info'): bool
    {
        try {
            $stmt = Database::connection()->prepare('INSERT INTO notifications(user_id,type,title,message,link) VALUES(?,?,?,?,?)');
            return $stmt->execute([$userId, $type, mb_substr($title, 0, 150), mb_substr($message, 0, 500), $link]);
        } catch (Throwable $e) {
            error_log('Notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Notify every active user with one of the given roles. */
    public static function roles(array $roles, string $title, string $message, ?string $link = null, string $type = 'info'): bool
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare('SELECT id FROM users WHERE role IN (' . implode(',', array_fill(0, count($roles), '?')) . ") AND status='active' AND verification_status='approved'");
            $stmt->execute($roles);
            $ok = true;
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $ok = self::send((int)$id, $title, $message, $link, $type) && $ok;
            }
            return $ok;
        } catch (Throwable $e) {
            error_log('Notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function admins(string $title, string $message, ?string $link = null, string $type = 'info'): bool
    {
        return self::roles(['admin'], $title, $message, $link, $type);
    }

    public static function staff(string $title, string $message, ?string $link = null, string $type = 'info'): bool
    {
        return self::roles(['admin', 'employee'], $title, $message, $link, $type);
    }

    /** Sends an email when email notifications are enabled. Returns delivery success. */
    public static function email(string $to, string $subject, string $heading, string $body, ?string $buttonText = null, ?string $buttonUrl = null, bool $force = false): bool
    {
        if (!$force && !Settings::enabled('email_notifications')) {
            return true; // emails switched off by admin: nothing to deliver
        }
        return Mailer::send($to, $subject, Mailer::template($heading, $body, $buttonText, $buttonUrl));
    }
}
