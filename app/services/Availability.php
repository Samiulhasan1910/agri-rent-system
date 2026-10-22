<?php
declare(strict_types=1);

/**
 * Equipment availability: peak simultaneous reservations per day, maintenance
 * windows and equipment status/health all reduce the number of free units.
 */
final class Availability
{
    /** Booking statuses that hold a unit. */
    public const HOLDING = ['pending', 'confirmed', 'delivered'];

    /** Reason equipment cannot be booked at all, or null when bookable. */
    public static function blockReason(array $equipment): ?string
    {
        if ($equipment['status'] === 'inactive') {
            return t('This equipment is no longer listed.');
        }
        if ($equipment['status'] === 'maintenance' || $equipment['health_status'] === 'under_maintenance') {
            return t('This equipment is under maintenance and cannot be booked right now.');
        }
        if ($equipment['status'] !== 'available') {
            return t('This equipment is not available for booking.');
        }
        if ($equipment['health_status'] === 'needs_maintenance' && Settings::enabled('block_needs_maintenance')) {
            return t('This equipment needs maintenance and cannot be booked until it is serviced.');
        }
        return null;
    }

    /**
     * Free units for each equipment row over [start, end] (inclusive).
     * @return array<int,int> equipment id => free units
     */
    public static function freeUnits(array $rows, string $start, string $end, int $excludeBooking = 0, bool $lock = false): array
    {
        if (!$rows) {
            return [];
        }
        $db = Database::connection();
        $ids = array_map(static fn($r) => (int)$r['id'], $rows);
        $marks = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT equipment_id,start_date,end_date FROM bookings WHERE equipment_id IN ($marks)
                AND status IN ('pending','confirmed','delivered') AND start_date <= ? AND end_date >= ? AND id <> ?" . ($lock ? ' FOR UPDATE' : '');
        $stmt = $db->prepare($sql);
        $stmt->execute([...$ids, $end, $start, $excludeBooking]);
        $grouped = [];
        foreach ($stmt->fetchAll() as $b) {
            $grouped[(int)$b['equipment_id']][] = $b;
        }

        $stmt = $db->prepare("SELECT DISTINCT equipment_id FROM maintenance WHERE equipment_id IN ($marks)
                AND status IN ('scheduled','in_progress') AND scheduled_date <= ? AND end_date >= ?");
        $stmt->execute([...$ids, $end, $start]);
        $inMaintenance = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $free = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            if (self::blockReason($row) !== null || in_array($id, $inMaintenance, true)) {
                $free[$id] = 0;
                continue;
            }
            $free[$id] = max(0, (int)$row['quantity'] - self::peak($grouped[$id] ?? [], $start, $end));
        }
        return $free;
    }

    /** Maintenance window overlapping the period, if any. */
    public static function maintenanceConflict(int $equipmentId, string $start, string $end): ?array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM maintenance WHERE equipment_id=? AND status IN ('scheduled','in_progress')
            AND scheduled_date <= ? AND end_date >= ? ORDER BY scheduled_date LIMIT 1");
        $stmt->execute([$equipmentId, $end, $start]);
        return $stmt->fetch() ?: null;
    }

    /** Highest number of bookings active on the same day inside [start, end]. */
    public static function peak(array $bookings, string $start, string $end): int
    {
        $events = [];
        foreach ($bookings as $b) {
            $from = max($start, $b['start_date']);
            $to = min($end, $b['end_date']);
            if ($from > $to) {
                continue;
            }
            $after = date('Y-m-d', strtotime($to . ' +1 day'));
            $events[$from] = ($events[$from] ?? 0) + 1;
            $events[$after] = ($events[$after] ?? 0) - 1;
        }
        ksort($events);
        $current = $peak = 0;
        foreach ($events as $change) {
            $current += $change;
            $peak = max($peak, $current);
        }
        return $peak;
    }
}
