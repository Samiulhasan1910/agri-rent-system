<?php
declare(strict_types=1);

class Booking extends Model
{
    public const MAX_DAYS = 90;

    /** Allowed staff transitions. returned -> completed happens through the return inspection. */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'rejected'],
        'confirmed' => ['delivered', 'rejected'],
        'delivered' => ['returned'],
    ];

    private const SELECT = "SELECT b.*, e.name equipment_name, e.image, e.specs, e.location equipment_location, e.owner_name, e.owner_phone,
            e.category_id, e.quantity, c.icon category_icon, c.name category_name,
            u.name farmer_name, u.email farmer_email, u.phone farmer_phone, u.address farmer_address, u.language farmer_language,
            d.id delivery_id, d.status delivery_status, d.scheduled_date, d.estimated_time, d.progress delivery_progress, d.team_id,
            t.name team_name, t.phone team_phone, t.leader_name team_leader,
            COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.booking_id=b.id AND p.status='paid'),0) paid_amount,
            COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.booking_id=b.id AND p.status='pending'),0) pending_amount,
            (SELECT p.status FROM payments p WHERE p.booking_id=b.id ORDER BY p.id DESC LIMIT 1) last_payment_status,
            (SELECT p.method FROM payments p WHERE p.booking_id=b.id ORDER BY p.id DESC LIMIT 1) last_payment_method,
            (SELECT COUNT(*) FROM payments p WHERE p.booking_id=b.id AND p.status='refunded') refunded_count,
            rv.id review_id, rv.rating review_rating,
            (SELECT x.id FROM booking_extensions x WHERE x.booking_id=b.id AND x.status='pending' ORDER BY x.id DESC LIMIT 1) pending_extension_id
        FROM bookings b
        JOIN equipment e ON e.id=b.equipment_id
        JOIN categories c ON c.id=e.category_id
        JOIN users u ON u.id=b.farmer_id
        LEFT JOIN deliveries d ON d.booking_id=b.id
        LEFT JOIN delivery_teams t ON t.id=d.team_id
        LEFT JOIN reviews rv ON rv.booking_id=b.id";

    /* ------------------------------------------------------------------
     * Derived values
     * ------------------------------------------------------------------ */
    public static function balance(array $b): float
    {
        if (in_array($b['status'], ['cancelled', 'rejected'], true)) {
            return 0.0;
        }
        return max(0.0, round((float)$b['total_amount'] - (float)$b['paid_amount'], 2));
    }

    public static function paymentState(array $b): string
    {
        if ((int)($b['refunded_count'] ?? 0) > 0 && in_array($b['status'], ['cancelled', 'rejected'], true)) {
            return 'refunded';
        }
        $paid = (float)$b['paid_amount'];
        if ($paid > 0 && $paid >= (float)$b['total_amount'] - 0.009) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }
        if ((float)($b['pending_amount'] ?? 0) > 0) {
            return 'pending';
        }
        if (($b['last_payment_status'] ?? '') === 'failed') {
            return 'failed';
        }
        return 'unpaid';
    }

    /** Farmer-facing label: confirmed & delivered rentals are "Active". */
    public static function farmerStatus(array $b): string
    {
        return in_array($b['status'], ['confirmed', 'delivered'], true) ? 'active_rental' : $b['status'];
    }

    public static function canCancel(array $b): bool
    {
        return in_array($b['status'], ['pending', 'confirmed'], true)
            && !in_array((string)($b['delivery_status'] ?? 'pending'), ['in_progress', 'delivered', 'returned'], true);
    }

    public static function canExtend(array $b): bool
    {
        return in_array($b['status'], ['confirmed', 'delivered'], true) && $b['end_date'] >= today() && empty($b['pending_extension_id']);
    }

    /* ------------------------------------------------------------------
     * Quotes & creation
     * ------------------------------------------------------------------ */

    /** Validates the period. Throws InvalidArgumentException with a user message. */
    public static function validatePeriod(string $start, string $end): int
    {
        if (!valid_date($start) || !valid_date($end)) {
            throw new InvalidArgumentException(t('Please select a valid start and end date.'));
        }
        if ($start < today()) {
            throw new InvalidArgumentException(t('The start date cannot be in the past.'));
        }
        if ($end < $start) {
            throw new InvalidArgumentException(t('End date cannot be earlier than start date.'));
        }
        $days = days_between($start, $end);
        if ($days > self::MAX_DAYS) {
            throw new InvalidArgumentException(t('A single booking can be at most :days days.', ['days' => self::MAX_DAYS]));
        }
        return $days;
    }

    /**
     * Price & availability quote for a rental period.
     * @throws InvalidArgumentException|DomainException
     */
    public function quote(array $equipment, string $start, string $end, int $excludeBooking = 0): array
    {
        $days = self::validatePeriod($start, $end);
        $price = Pricing::quote($equipment['price_per_day'], $start);
        $rental = round($price['price'] * $days, 2);
        $delivery = round((float)setting('delivery_charge', 300), 2);
        $free = Availability::freeUnits([$equipment], $start, $end, $excludeBooking)[(int)$equipment['id']] ?? 0;
        $maintenance = Availability::maintenanceConflict((int)$equipment['id'], $start, $end);
        return [
            'days' => $days,
            'base_price' => $price['base'],
            'price_per_day' => $price['price'],
            'season' => $price['season'],
            'season_label' => Pricing::label($price['season']),
            'percent' => $price['percent'],
            'rental_cost' => $rental,
            'delivery_charge' => $delivery,
            'total' => round($rental + $delivery, 2),
            'available_units' => $free,
            'block_reason' => Availability::blockReason($equipment),
            'maintenance' => $maintenance ? ['from' => $maintenance['scheduled_date'], 'to' => $maintenance['end_date']] : null,
        ];
    }

    /**
     * Creates a pending booking with row locking so two farmers cannot take the last unit.
     * @throws InvalidArgumentException|DomainException
     */
    public function create(int $farmerId, int $equipmentId, string $start, string $end, string $address, string $notes = '', ?string $uuid = null): int
    {
        return $this->transaction(function () use ($farmerId, $equipmentId, $start, $end, $address, $notes, $uuid): int {
            $days = self::validatePeriod($start, $end);
            $equipment = $this->one('SELECT * FROM equipment WHERE id=? FOR UPDATE', [$equipmentId]);
            if (!$equipment) {
                throw new DomainException(t('Equipment not found.'));
            }
            if ($reason = Availability::blockReason($equipment)) {
                throw new DomainException($reason);
            }
            if ($m = Availability::maintenanceConflict($equipmentId, $start, $end)) {
                throw new DomainException(t('This equipment is scheduled for maintenance from :from to :to. Please choose other dates.', ['from' => fmt_date($m['scheduled_date']), 'to' => fmt_date($m['end_date'])]));
            }
            $free = Availability::freeUnits([$equipment], $start, $end, 0, true)[$equipmentId] ?? 0;
            if ($free < 1) {
                throw new DomainException(t('The equipment is already fully booked for these dates. Please choose another period.'));
            }
            $price = Pricing::quote($equipment['price_per_day'], $start);
            $rental = round($price['price'] * $days, 2);
            $delivery = round((float)setting('delivery_charge', 300), 2);

            $id = $this->insert('bookings', [
                'farmer_id' => $farmerId,
                'equipment_id' => $equipmentId,
                'start_date' => $start,
                'end_date' => $end,
                'days' => $days,
                'base_price' => $price['base'],
                'price_per_day' => $price['price'],
                'season' => $price['season'],
                'rental_cost' => $rental,
                'delivery_charge' => $delivery,
                'total_amount' => round($rental + $delivery, 2),
                'delivery_address' => mb_substr($address, 0, 255),
                'notes' => $notes !== '' ? mb_substr($notes, 0, 500) : null,
                'client_uuid' => $uuid,
            ]);
            $this->run('UPDATE bookings SET booking_code=? WHERE id=?', ['AGR' . str_pad((string)$id, 5, '0', STR_PAD_LEFT), $id]);
            $this->insert('deliveries', ['booking_id' => $id, 'address' => mb_substr($address, 0, 255)]);
            return $id;
        });
    }

    /* ------------------------------------------------------------------
     * Queries
     * ------------------------------------------------------------------ */
    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE b.id=?', [$id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->one('SELECT * FROM bookings WHERE client_uuid=?', [$uuid]);
    }

    public function forFarmer(int $farmerId, array $f, int $page = 1, int $per = 6): array
    {
        $where = ['b.farmer_id=?'];
        $params = [$farmerId];
        $tabs = [
            'active' => "b.status IN ('confirmed','delivered','returned')",
            'pending' => "b.status='pending'",
            'cancelled' => "b.status IN ('cancelled','rejected')",
            'completed' => "b.status='completed'",
        ];
        if (isset($tabs[$f['tab'] ?? ''])) {
            $where[] = $tabs[$f['tab']];
        }
        if (!empty($f['q'])) {
            $where[] = '(e.name LIKE ? OR b.booking_code LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%");
        }
        return $this->paged($where, $params, $page, $per, 'b.id DESC');
    }

    public function listAll(array $f, int $page = 1, int $per = 10): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(b.booking_code LIKE ? OR u.name LIKE ? OR e.name LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['equipment'])) {
            $where[] = 'b.equipment_id=?';
            $params[] = (int)$f['equipment'];
        }
        if (!empty($f['status'])) {
            $where[] = 'b.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['from'])) {
            $where[] = 'b.end_date >= ?';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 'b.start_date <= ?';
            $params[] = $f['to'];
        }
        return $this->paged($where, $params, $page, $per, "FIELD(b.status,'pending','confirmed','delivered','returned','completed','cancelled','rejected'), b.start_date DESC");
    }

    /** All rows for exports/reports (no pagination). */
    public function export(array $where, array $params): array
    {
        return $this->all(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY b.start_date DESC, b.id DESC', $params);
    }

    private function paged(array $where, array $params, int $page, int $per, string $order): array
    {
        $sqlWhere = implode(' AND ', $where);
        $total = (int)$this->value("SELECT COUNT(*) FROM bookings b JOIN equipment e ON e.id=b.equipment_id JOIN users u ON u.id=b.farmer_id WHERE {$sqlWhere}", $params);
        $meta = paginate($total, $page, $per);
        $rows = $this->all(self::SELECT . " WHERE {$sqlWhere} ORDER BY {$order} LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function latestForFarmer(int $farmerId): ?array
    {
        // the rental currently on the farm first, then upcoming ones, then history
        return $this->one(self::SELECT . " WHERE b.farmer_id=? AND b.status NOT IN ('cancelled','rejected')
            ORDER BY FIELD(b.status,'delivered','confirmed','pending','returned','completed'), b.start_date ASC LIMIT 1", [$farmerId]);
    }

    public function recent(int $limit = 6): array
    {
        return $this->all(self::SELECT . ' ORDER BY b.id DESC LIMIT ' . (int)$limit);
    }

    public function pendingRequests(int $limit = 6): array
    {
        return $this->all(self::SELECT . " WHERE b.status='pending' ORDER BY b.created_at ASC LIMIT " . (int)$limit);
    }

    public function awaitingInspection(): array
    {
        return $this->all(self::SELECT . " WHERE b.status='returned' AND NOT EXISTS (SELECT 1 FROM inspections i WHERE i.booking_id=b.id) ORDER BY b.end_date ASC");
    }

    public function countByStatus(?int $farmerId = null): array
    {
        $sql = 'SELECT status, COUNT(*) n FROM bookings' . ($farmerId ? ' WHERE farmer_id=?' : '') . ' GROUP BY status';
        $counts = array_fill_keys(['pending', 'confirmed', 'delivered', 'returned', 'completed', 'cancelled', 'rejected'], 0);
        foreach ($this->all($sql, $farmerId ? [$farmerId] : []) as $row) {
            $counts[$row['status']] = (int)$row['n'];
        }
        return $counts;
    }

    /** Return reminders: active rentals ending within the next 2 days. */
    public function endingSoon(int $farmerId): array
    {
        return $this->all("SELECT b.id, b.booking_code, b.end_date, e.name equipment_name FROM bookings b JOIN equipment e ON e.id=b.equipment_id
            WHERE b.farmer_id=? AND b.status IN ('confirmed','delivered') AND b.end_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 2 DAY ORDER BY b.end_date", [$farmerId]);
    }

    public function withBalance(int $farmerId): array
    {
        $rows = $this->all(self::SELECT . " WHERE b.farmer_id=? AND b.status IN ('pending','confirmed','delivered','returned','completed') ORDER BY b.id DESC", [$farmerId]);
        return array_values(array_filter($rows, static fn($b) => self::balance($b) > 0));
    }

    public function hasActiveRentals(int $farmerId): bool
    {
        return (bool)$this->value("SELECT COUNT(*) FROM bookings WHERE farmer_id=? AND status IN ('pending','confirmed','delivered','returned')", [$farmerId]);
    }

    /* ------------------------------------------------------------------
     * State changes
     * ------------------------------------------------------------------ */

    /** Staff status update with transition rules; keeps delivery & payments in sync. */
    public function changeStatus(int $id, string $to, int $staffId, ?string $note = null): array
    {
        return $this->transaction(function () use ($id, $to, $staffId, $note): array {
            $booking = $this->one('SELECT * FROM bookings WHERE id=? FOR UPDATE', [$id]);
            if (!$booking) {
                throw new DomainException(t('Booking not found.'));
            }
            if (!in_array($to, self::TRANSITIONS[$booking['status']] ?? [], true)) {
                throw new DomainException(t('This rental cannot move from :from to :to.', ['from' => status_meta($booking['status'])[0], 'to' => status_meta($to)[0]]));
            }
            $this->run('UPDATE bookings SET status=?, status_note=? WHERE id=?', [$to, $note, $id]);
            $delivery = $this->one('SELECT * FROM deliveries WHERE booking_id=?', [$id]);
            if ($delivery) {
                $map = ['delivered' => 'delivered', 'returned' => 'returned', 'rejected' => 'cancelled'];
                if (isset($map[$to])) {
                    $this->run("UPDATE deliveries SET status=?, progress=IF(?='cancelled',progress,100),
                        delivered_at=IF(?='delivered',NOW(),delivered_at), returned_at=IF(?='returned',NOW(),returned_at) WHERE id=?",
                        [$map[$to], $map[$to], $map[$to], $map[$to], $delivery['id']]);
                    $this->insert('delivery_updates', ['delivery_id' => $delivery['id'], 'status' => $map[$to], 'note' => $note ?: t('Updated from rental tracking'), 'updated_by' => $staffId]);
                }
            }
            if ($to === 'rejected') {
                $this->releasePayments($id);
                $this->run("UPDATE booking_extensions SET status='cancelled' WHERE booking_id=? AND status='pending'", [$id]);
            }
            return $booking;
        });
    }

    /** Farmer cancellation (only before delivery). */
    public function cancel(int $id, int $farmerId, string $reason, string $details): array
    {
        return $this->transaction(function () use ($id, $farmerId, $reason, $details): array {
            $booking = $this->one('SELECT b.*, d.status delivery_status FROM bookings b LEFT JOIN deliveries d ON d.booking_id=b.id WHERE b.id=? AND b.farmer_id=? FOR UPDATE', [$id, $farmerId]);
            if (!$booking) {
                throw new DomainException(t('Booking not found.'));
            }
            if (!self::canCancel($booking)) {
                throw new DomainException(t('Cancellation not allowed after delivery.'));
            }
            $this->run("UPDATE bookings SET status='cancelled', cancellation_reason=?, cancellation_details=?, cancelled_at=NOW() WHERE id=?", [$reason, $details !== '' ? $details : null, $id]);
            $this->run("UPDATE deliveries SET status='cancelled' WHERE booking_id=?", [$id]);
            $this->run("UPDATE booking_extensions SET status='cancelled' WHERE booking_id=? AND status='pending'", [$id]);
            $refunded = $this->releasePayments($id);
            return $booking + ['refunded' => $refunded];
        });
    }

    /** Paid payments become refunded; pending (cash) payments are cancelled. Returns refunded amount. */
    private function releasePayments(int $bookingId): float
    {
        $refund = (float)$this->value("SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id=? AND status='paid'", [$bookingId]);
        $this->run("UPDATE payments SET status='refunded' WHERE booking_id=? AND status='paid'", [$bookingId]);
        $this->run("UPDATE payments SET status='cancelled' WHERE booking_id=? AND status='pending'", [$bookingId]);
        return $refund;
    }

    public function complete(int $id): void
    {
        $this->run("UPDATE bookings SET status='completed' WHERE id=? AND status='returned'", [$id]);
    }

    /** Applies a coupon discount to the booking total. */
    public function applyDiscount(int $id, float $discount): void
    {
        $this->run('UPDATE bookings SET discount=discount+?, total_amount=GREATEST(0,total_amount-?) WHERE id=?', [$discount, $discount, $id]);
    }
}
