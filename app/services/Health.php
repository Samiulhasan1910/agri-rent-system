<?php
declare(strict_types=1);

/**
 * Equipment health monitoring:
 *  - usage above the threshold or an overdue service interval => "Needs Maintenance" + alert
 *  - recently serviced => "Good"
 *  - missing usage data => warning flag ("Data Missing") + alert
 *  - active maintenance => "Under Maintenance"
 */
final class Health
{
    public static function evaluate(int $equipmentId, bool $notify = true): string
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM equipment WHERE id=?');
        $stmt->execute([$equipmentId]);
        $eq = $stmt->fetch();
        if (!$eq) {
            return 'unknown';
        }

        $inProgress = (int)self::value("SELECT COUNT(*) FROM maintenance WHERE equipment_id=? AND status='in_progress'", [$equipmentId]);
        if ($eq['status'] === 'maintenance' || $inProgress > 0) {
            $state = 'under_maintenance';
        } else {
            $state = 'good';
            if ((float)$eq['hours_since_service'] >= (float)$eq['usage_threshold_hours']) {
                self::alert($equipmentId, 'usage_threshold', t(':name exceeded its :limit h service threshold (:hours h since last service).', [
                    'name' => $eq['name'], 'limit' => (int)$eq['usage_threshold_hours'], 'hours' => (float)$eq['hours_since_service'],
                ]));
                $state = 'needs_maintenance';
            }
            if ($eq['last_service_date'] && strtotime($eq['last_service_date'] . ' +' . (int)$eq['service_interval_days'] . ' days') < strtotime(today())) {
                self::alert($equipmentId, 'service_due', t(':name service is overdue (interval :days days, last serviced :date).', [
                    'name' => $eq['name'], 'days' => (int)$eq['service_interval_days'], 'date' => fmt_date($eq['last_service_date']),
                ]));
                $state = 'needs_maintenance';
            }
            if ($state === 'good' && !empty($eq['health_flag'])) {
                $state = 'unknown';
            }
            if ($state === 'good' && $eq['status'] === 'unavailable' && self::value("SELECT COUNT(*) FROM maintenance_alerts WHERE equipment_id=? AND type='damaged' AND status='open'", [$equipmentId])) {
                $state = 'needs_maintenance';
            }
        }

        if ($state !== $eq['health_status']) {
            $db->prepare('UPDATE equipment SET health_status=? WHERE id=?')->execute([$state, $equipmentId]);
            if ($state === 'needs_maintenance' && $notify && Settings::enabled('maintenance_alert')) {
                Notifier::staff('Maintenance needed', t(':name needs maintenance and is blocked from new bookings.', ['name' => $eq['name']]), url('maintenance/health'), 'warning');
            }
        }
        return $state;
    }

    /** Recalculate all equipment (used by dashboards so overdue intervals are detected daily). */
    public static function evaluateAll(): void
    {
        $ids = Database::connection()->query("SELECT id FROM equipment WHERE status <> 'inactive'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            self::evaluate((int)$id);
        }
    }

    /** Record usage hours after a return (null hours = missing data). */
    public static function recordUsage(int $equipmentId, ?int $bookingId, ?float $hours, ?int $userId, string $source = 'return', ?string $note = null): void
    {
        $db = Database::connection();
        $db->prepare('INSERT INTO usage_logs(equipment_id,booking_id,hours,source,note,flagged,logged_by) VALUES(?,?,?,?,?,?,?)')
            ->execute([$equipmentId, $bookingId, $hours, $source, $note, $hours === null ? 1 : 0, $userId]);

        if ($hours === null) {
            $code = $bookingId ? (string)self::value('SELECT booking_code FROM bookings WHERE id=?', [$bookingId]) : '';
            $flag = $code !== '' ? t('Usage hours were not recorded for the last return (:code).', ['code' => $code]) : t('Usage hours were not recorded.');
            $db->prepare('UPDATE equipment SET health_flag=? WHERE id=?')->execute([$flag, $equipmentId]);
            self::alert($equipmentId, 'missing_data', $flag);
        } else {
            $db->prepare('UPDATE equipment SET usage_hours=usage_hours+?, hours_since_service=hours_since_service+?, health_flag=NULL WHERE id=?')
                ->execute([$hours, $hours, $equipmentId]);
            $db->prepare("UPDATE maintenance_alerts SET status='resolved', resolved_at=NOW() WHERE equipment_id=? AND type='missing_data' AND status='open'")
                ->execute([$equipmentId]);
        }
        self::evaluate($equipmentId);
    }

    /** Called when maintenance is completed: reset counters, resolve alerts and mark "Good". */
    public static function markServiced(int $equipmentId): void
    {
        $db = Database::connection();
        $db->prepare("UPDATE equipment SET hours_since_service=0, last_service_date=CURDATE(), health_flag=NULL,
            status=IF(status IN ('maintenance','unavailable'),'available',status) WHERE id=?")->execute([$equipmentId]);
        $db->prepare("UPDATE maintenance_alerts SET status='resolved', resolved_at=NOW() WHERE equipment_id=? AND status='open'")
            ->execute([$equipmentId]);
        $db->prepare("UPDATE equipment SET health_status='good' WHERE id=?")->execute([$equipmentId]);
        self::evaluate($equipmentId);
    }

    public static function alert(int $equipmentId, string $type, string $message): void
    {
        $exists = self::value("SELECT id FROM maintenance_alerts WHERE equipment_id=? AND type=? AND status='open'", [$equipmentId, $type]);
        if (!$exists) {
            Database::connection()->prepare('INSERT INTO maintenance_alerts(equipment_id,type,message) VALUES(?,?,?)')
                ->execute([$equipmentId, $type, mb_substr($message, 0, 255)]);
        }
    }

    private static function value(string $sql, array $params)
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? null : $value;
    }
}
