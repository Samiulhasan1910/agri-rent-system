<?php
declare(strict_types=1);

/** Aggregations for the admin analytics dashboard. */
class Analytics extends Model
{
    private const BILLABLE = "('confirmed','delivered','returned','completed')";

    private function filter(int $equipmentId, array &$params, string $alias = 'b'): string
    {
        if ($equipmentId > 0) {
            $params[] = $equipmentId;
            return " AND {$alias}.equipment_id=?";
        }
        return '';
    }

    public function summary(string $from, string $to, int $equipmentId = 0): array
    {
        $p = [$from, $to];
        $eq = $this->filter($equipmentId, $p);
        $bookings = $this->one("SELECT COUNT(*) bookings, SUM(b.status IN " . self::BILLABLE . ") billable,
                COALESCE(SUM(CASE WHEN b.status IN " . self::BILLABLE . " THEN b.total_amount END),0) booking_value,
                SUM(b.status IN ('cancelled','rejected')) cancelled, COALESCE(SUM(CASE WHEN b.status IN " . self::BILLABLE . " THEN b.days END),0) rental_days,
                SUM(b.status='completed') completed, SUM(b.status IN ('returned','completed')) returned_total, SUM(b.status IN ('delivered','returned','completed')) delivered_total
            FROM bookings b WHERE DATE(b.created_at) BETWEEN ? AND ?{$eq}", $p);

        $p2 = [$from, $to];
        $eq2 = $this->filter($equipmentId, $p2);
        $revenue = (float)$this->value("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id
            WHERE p.status='paid' AND DATE(COALESCE(p.paid_at,p.created_at)) BETWEEN ? AND ?{$eq2}", $p2);

        $delivered = (int)($bookings['delivered_total'] ?? 0);
        return [
            'revenue' => $revenue,
            'bookings' => (int)($bookings['bookings'] ?? 0),
            'billable' => (int)($bookings['billable'] ?? 0),
            'booking_value' => (float)($bookings['booking_value'] ?? 0),
            'cancelled' => (int)($bookings['cancelled'] ?? 0),
            'rental_days' => (int)($bookings['rental_days'] ?? 0),
            'avg_value' => (int)($bookings['billable'] ?? 0) > 0 ? round((float)$bookings['booking_value'] / (int)$bookings['billable'], 2) : 0,
            'return_rate' => $delivered > 0 ? round((int)$bookings['returned_total'] / $delivered * 100, 1) : 0,
        ];
    }

    /** Daily or monthly series of revenue (paid) and bookings. */
    public function series(string $from, string $to, int $equipmentId = 0): array
    {
        $days = days_between($from, $to);
        $monthly = $days > 62;
        $fmt = $monthly ? '%Y-%m' : '%Y-%m-%d';

        $p = [$from, $to];
        $eq = $this->filter($equipmentId, $p);
        $revenue = [];
        foreach ($this->all("SELECT DATE_FORMAT(COALESCE(p.paid_at,p.created_at),'{$fmt}') k, SUM(p.amount) v FROM payments p JOIN bookings b ON b.id=p.booking_id
            WHERE p.status='paid' AND DATE(COALESCE(p.paid_at,p.created_at)) BETWEEN ? AND ?{$eq} GROUP BY k", $p) as $r) {
            $revenue[$r['k']] = (float)$r['v'];
        }
        $p = [$from, $to];
        $eq = $this->filter($equipmentId, $p);
        $bookings = [];
        foreach ($this->all("SELECT DATE_FORMAT(b.created_at,'{$fmt}') k, COUNT(*) v FROM bookings b WHERE DATE(b.created_at) BETWEEN ? AND ?{$eq} GROUP BY k", $p) as $r) {
            $bookings[$r['k']] = (int)$r['v'];
        }

        $labels = $rev = $book = [];
        $cursor = new DateTime($monthly ? date('Y-m-01', strtotime($from)) : $from);
        $end = new DateTime($to);
        while ($cursor <= $end) {
            $key = $cursor->format($monthly ? 'Y-m' : 'Y-m-d');
            $labels[] = $monthly ? $cursor->format('M Y') : $cursor->format('d M');
            $rev[] = $revenue[$key] ?? 0;
            $book[] = $bookings[$key] ?? 0;
            $cursor->modify($monthly ? '+1 month' : '+1 day');
        }
        return ['labels' => $labels, 'revenue' => $rev, 'bookings' => $book, 'granularity' => $monthly ? 'month' : 'day'];
    }

    /** Rental days per equipment (usage) in the range. */
    public function usage(string $from, string $to, int $equipmentId = 0, int $limit = 6): array
    {
        $p = [$to, $from];
        $eq = $this->filter($equipmentId, $p);
        return $this->all("SELECT e.name label, SUM(b.days) value FROM bookings b JOIN equipment e ON e.id=b.equipment_id
            WHERE b.status IN " . self::BILLABLE . " AND b.start_date <= ? AND b.end_date >= ?{$eq}
            GROUP BY e.id ORDER BY value DESC LIMIT " . (int)$limit, $p);
    }

    public function byCategory(string $from, string $to, int $equipmentId = 0): array
    {
        $p = [$from, $to];
        $eq = $this->filter($equipmentId, $p);
        return $this->all("SELECT c.name label, COUNT(*) value FROM bookings b JOIN equipment e ON e.id=b.equipment_id JOIN categories c ON c.id=e.category_id
            WHERE DATE(b.created_at) BETWEEN ? AND ?{$eq} GROUP BY c.id ORDER BY value DESC", $p);
    }

    public function statusBreakdown(string $from, string $to, int $equipmentId = 0): array
    {
        $p = [$from, $to];
        $eq = $this->filter($equipmentId, $p);
        return $this->all("SELECT b.status label, COUNT(*) value FROM bookings b WHERE DATE(b.created_at) BETWEEN ? AND ?{$eq} GROUP BY b.status ORDER BY value DESC", $p);
    }

    /** Monthly bookings for the admin dashboard bar chart (last 7 months). */
    public function monthlyBookings(int $months = 7): array
    {
        $from = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $rows = [];
        foreach ($this->all("SELECT DATE_FORMAT(created_at,'%Y-%m') k, COUNT(*) v FROM bookings WHERE created_at >= ? GROUP BY k", [$from]) as $r) {
            $rows[$r['k']] = (int)$r['v'];
        }
        $labels = $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} months");
            $labels[] = date('M', $ts);
            $values[] = $rows[date('Y-m', $ts)] ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
