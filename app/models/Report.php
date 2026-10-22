<?php
declare(strict_types=1);

/**
 * Sales, payment and rental reports with an integrity check:
 * the sum of the listed rows must equal an independent SQL aggregate,
 * otherwise a "Report data mismatch" error is raised.
 */
class Report extends Model
{
    public const TYPES = ['sales' => 'Sales Report', 'payment' => 'Payment Report', 'rental' => 'Rental Report'];

    /**
     * @return array{columns: array, rows: array, total: float, count: int, checksum: string}
     * @throws DomainException on missing data or mismatch
     */
    public function build(string $type, string $from, string $to): array
    {
        switch ($type) {
            case 'sales':
                $rows = $this->all("SELECT b.booking_code, DATE(b.created_at) booked_on, u.name farmer, e.name equipment, b.days, b.rental_cost, b.delivery_charge, b.discount, b.total_amount amount
                    FROM bookings b JOIN users u ON u.id=b.farmer_id JOIN equipment e ON e.id=b.equipment_id
                    WHERE b.status IN ('confirmed','delivered','returned','completed') AND DATE(b.created_at) BETWEEN ? AND ? ORDER BY b.created_at", [$from, $to]);
                $aggregate = (float)$this->value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('confirmed','delivered','returned','completed') AND DATE(created_at) BETWEEN ? AND ?", [$from, $to]);
                $columns = [['Booking', 62], ['Booked on', 62], ['Farmer', 100], ['Equipment', 130], ['Days', 36, 'R'], ['Rental', 70, 'R'], ['Delivery', 60, 'R'], ['Discount', 60, 'R'], ['Total', 75, 'R']];
                $map = static fn($r) => [$r['booking_code'], fmt_date($r['booked_on']), $r['farmer'], $r['equipment'], $r['days'], self::amt($r['rental_cost']), self::amt($r['delivery_charge']), self::amt($r['discount']), self::amt($r['amount'])];
                break;
            case 'payment':
                $rows = $this->all("SELECT p.invoice_no, DATE(p.created_at) paid_on, b.booking_code, u.name farmer, p.method, p.transaction_id, p.status, p.amount
                    FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN users u ON u.id=b.farmer_id
                    WHERE DATE(p.created_at) BETWEEN ? AND ? ORDER BY p.created_at", [$from, $to]);
                $aggregate = (float)$this->value('SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(created_at) BETWEEN ? AND ?', [$from, $to]);
                $columns = [['Invoice', 80], ['Date', 62], ['Booking', 62], ['Farmer', 110], ['Method', 70], ['Transaction', 95], ['Status', 60], ['Amount', 80, 'R']];
                $map = static fn($r) => [$r['invoice_no'], fmt_date($r['paid_on']), $r['booking_code'], $r['farmer'], Payment::METHODS[$r['method']] ?? $r['method'], $r['transaction_id'] ?: '-', ucfirst($r['status']), self::amt($r['amount'])];
                break;
            case 'rental':
                $rows = $this->all("SELECT b.booking_code, u.name farmer, e.name equipment, b.start_date, b.end_date, b.days, b.status, b.total_amount amount
                    FROM bookings b JOIN users u ON u.id=b.farmer_id JOIN equipment e ON e.id=b.equipment_id
                    WHERE b.start_date <= ? AND b.end_date >= ? ORDER BY b.start_date", [$to, $from]);
                $aggregate = (float)$this->value('SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE start_date <= ? AND end_date >= ?', [$to, $from]);
                $columns = [['Booking', 62], ['Farmer', 110], ['Equipment', 140], ['Start', 62], ['End', 62], ['Days', 36, 'R'], ['Status', 70], ['Amount', 80, 'R']];
                $map = static fn($r) => [$r['booking_code'], $r['farmer'], $r['equipment'], fmt_date($r['start_date']), fmt_date($r['end_date']), $r['days'], ucfirst($r['status']), self::amt($r['amount'])];
                break;
            default:
                throw new DomainException(t('Failed to generate report. Please try again.'));
        }

        $total = 0.0;
        foreach ($rows as $r) {
            if (!isset($r['amount']) || $r['amount'] === null) {
                throw new DomainException(t('Report data is unavailable or inaccurate. Please refresh and try again.'));
            }
            $total += (float)$r['amount'];
        }
        if (abs(round($total, 2) - round($aggregate, 2)) > 0.009) {
            throw new DomainException(t('Report data mismatch detected. Please verify system records.'));
        }
        return [
            'columns' => array_map(static fn($c) => ['label' => $c[0], 'width' => $c[1], 'align' => $c[2] ?? 'L'], $columns),
            'rows' => array_map($map, $rows),
            'total' => round($total, 2),
            'count' => count($rows),
            'checksum' => sha1(json_encode($rows)),
        ];
    }

    public function log(string $type, string $from, string $to, array $data, int $userId): array
    {
        $id = $this->insert('reports', [
            'report_no' => 'TMP-' . bin2hex(random_bytes(6)),
            'type' => $type,
            'date_from' => $from,
            'date_to' => $to,
            'row_count' => $data['count'],
            'total_amount' => $data['total'],
            'checksum' => $data['checksum'],
            'generated_by' => $userId,
        ]);
        $no = 'RPT-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $this->run('UPDATE reports SET report_no=? WHERE id=?', [$no, $id]);
        return $this->find($id);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT r.*, u.name generated_by_name FROM reports r LEFT JOIN users u ON u.id=r.generated_by WHERE r.id=?', [$id]);
    }

    public function recent(int $limit = 10): array
    {
        return $this->all('SELECT r.*, u.name generated_by_name FROM reports r LEFT JOIN users u ON u.id=r.generated_by ORDER BY r.id DESC LIMIT ' . (int)$limit);
    }

    private static function amt($value): string
    {
        return 'Tk ' . number_format((float)$value, 2);
    }
}
