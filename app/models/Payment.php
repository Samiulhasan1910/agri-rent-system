<?php
declare(strict_types=1);

class Payment extends Model
{
    public const METHODS = [
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'card' => 'Credit / Debit Card',
        'cash' => 'Cash on Delivery',
    ];

    private const SELECT = 'SELECT p.*, b.booking_code, b.farmer_id, b.total_amount booking_total, b.start_date, b.end_date, b.days, b.price_per_day,
            b.rental_cost, b.delivery_charge, b.discount booking_discount, b.status booking_status, b.delivery_address,
            e.name equipment_name, u.name farmer_name, u.email farmer_email, u.phone farmer_phone, c.code coupon_code
        FROM payments p
        JOIN bookings b ON b.id=p.booking_id
        JOIN equipment e ON e.id=b.equipment_id
        JOIN users u ON u.id=b.farmer_id
        LEFT JOIN coupons c ON c.id=p.coupon_id';

    public static function enabledMethods(): array
    {
        return array_filter(self::METHODS, static fn($key) => Settings::enabled('method_' . $key), ARRAY_FILTER_USE_KEY);
    }

    public static function label(string $method): string
    {
        return t(self::METHODS[$method] ?? ucfirst($method));
    }

    public function create(array $data): int
    {
        $data['invoice_no'] = 'TMP-' . bin2hex(random_bytes(6));
        $id = $this->insert('payments', $data);
        $this->run('UPDATE payments SET invoice_no=? WHERE id=?', ['INV-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), $id]);
        return $id;
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE p.id=?', [$id]);
    }

    public function findByInvoice(string $invoice): ?array
    {
        return $this->one(self::SELECT . ' WHERE p.invoice_no=?', [$invoice]);
    }

    public function forBooking(int $bookingId): array
    {
        return $this->all('SELECT p.*, c.code coupon_code FROM payments p LEFT JOIN coupons c ON c.id=p.coupon_id WHERE p.booking_id=? ORDER BY p.id DESC', [$bookingId]);
    }

    public function hasPendingCash(int $bookingId): bool
    {
        return (bool)$this->value("SELECT COUNT(*) FROM payments WHERE booking_id=? AND method='cash' AND status='pending'", [$bookingId]);
    }

    private function filters(array $f, array &$params): string
    {
        $where = ['1=1'];
        if (!empty($f['farmer_id'])) {
            $where[] = 'b.farmer_id=?';
            $params[] = (int)$f['farmer_id'];
        }
        if (!empty($f['q'])) {
            $where[] = '(p.invoice_no LIKE ? OR b.booking_code LIKE ? OR p.transaction_id LIKE ? OR u.name LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['status'])) {
            $where[] = 'p.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['method'])) {
            $where[] = 'p.method=?';
            $params[] = $f['method'];
        }
        if (!empty($f['from'])) {
            $where[] = 'DATE(p.created_at) >= ?';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 'DATE(p.created_at) <= ?';
            $params[] = $f['to'];
        }
        return implode(' AND ', $where);
    }

    public function paginateList(array $f, int $page, int $per = 10): array
    {
        $params = [];
        $where = $this->filters($f, $params);
        $meta = paginate((int)$this->value("SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN users u ON u.id=b.farmer_id WHERE {$where}", $params), $page, $per);
        $rows = $this->all(self::SELECT . " WHERE {$where} ORDER BY p.id DESC LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function exportRows(array $f): array
    {
        $params = [];
        $where = $this->filters($f, $params);
        return $this->all(self::SELECT . " WHERE {$where} ORDER BY p.id DESC", $params);
    }

    public function stats(?int $farmerId = null): array
    {
        $params = [];
        $where = '';
        if ($farmerId) {
            $where = 'WHERE b.farmer_id=?';
            $params[] = $farmerId;
        }
        $row = $this->one("SELECT COUNT(*) total_count,
                COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount END),0) paid_total,
                COALESCE(SUM(CASE WHEN p.status='pending' THEN p.amount END),0) pending_total,
                SUM(p.status='paid') paid_count, SUM(p.status='pending') pending_count, SUM(p.status='failed') failed_count,
                SUM(p.invoice_generated_at IS NOT NULL) invoice_count
            FROM payments p JOIN bookings b ON b.id=p.booking_id {$where}", $params);
        return array_map(static fn($v) => $v === null ? 0 : $v, $row ?: []);
    }

    public function markPaid(int $id): int
    {
        return $this->run("UPDATE payments SET status='paid', paid_at=NOW(), transaction_id=COALESCE(transaction_id, CONCAT('CASH-', LPAD(id,4,'0'))) WHERE id=? AND status='pending'", [$id])->rowCount();
    }

    public function markInvoiceGenerated(int $id): void
    {
        $this->run('UPDATE payments SET invoice_generated_at=COALESCE(invoice_generated_at, NOW()) WHERE id=?', [$id]);
    }

    public function markReceiptEmailed(int $id): void
    {
        $this->run('UPDATE payments SET receipt_emailed=1 WHERE id=?', [$id]);
    }

    public function revenueBetween(string $from, string $to): float
    {
        return (float)$this->value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND DATE(COALESCE(paid_at,created_at)) BETWEEN ? AND ?", [$from, $to]);
    }

    public function recentFailures(int $limit = 5): array
    {
        return $this->all(self::SELECT . " WHERE p.status='failed' ORDER BY p.id DESC LIMIT " . (int)$limit);
    }
}
