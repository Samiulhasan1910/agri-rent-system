<?php
declare(strict_types=1);

/** Rental extension requests. */
class Extension extends Model
{
    /**
     * Validates an extension and returns the calculation (extra days, extra cost, new total).
     * @throws DomainException|InvalidArgumentException
     */
    public function calculate(array $booking, string $newEnd): array
    {
        if (!in_array($booking['status'], ['confirmed', 'delivered'], true) || $booking['end_date'] < today()) {
            throw new DomainException(t('Extension request cannot be processed. Please try again before the rental expires.'));
        }
        if (!valid_date($newEnd)) {
            throw new InvalidArgumentException(t('Please select a valid new return date.'));
        }
        if ($newEnd <= $booking['end_date']) {
            throw new InvalidArgumentException(t('The new return date must be after the current return date.'));
        }
        $extraDays = days_between($booking['end_date'], $newEnd) - 1;
        if ((int)$booking['days'] + $extraDays > Booking::MAX_DAYS) {
            throw new InvalidArgumentException(t('A single booking can be at most :days days.', ['days' => Booking::MAX_DAYS]));
        }
        $rate = (float)$booking['price_per_day'];
        if ($rate <= 0) {
            throw new DomainException(t('Failed to calculate updated rental details. Please try again.'));
        }
        $equipment = $this->one('SELECT * FROM equipment WHERE id=?', [$booking['equipment_id']]);
        $firstExtraDay = date('Y-m-d', strtotime($booking['end_date'] . ' +1 day'));
        // The rental already holds a unit, so only other bookings and maintenance windows can block the extra days.
        $conflict = Availability::maintenanceConflict((int)$equipment['id'], $firstExtraDay, $newEnd);
        $bookedPeak = Availability::peak($this->all("SELECT start_date,end_date FROM bookings WHERE equipment_id=? AND id<>? AND status IN ('pending','confirmed','delivered') AND start_date<=? AND end_date>=?", [$equipment['id'], $booking['id'], $newEnd, $firstExtraDay]), $firstExtraDay, $newEnd);
        if ($conflict || $bookedPeak >= (int)$equipment['quantity']) {
            throw new DomainException(t('Equipment is not available for the selected extended period.'));
        }
        $extraCost = round($rate * $extraDays, 2);
        return [
            'current_end' => $booking['end_date'],
            'new_end' => $newEnd,
            'extra_days' => $extraDays,
            'extra_cost' => $extraCost,
            'new_days' => (int)$booking['days'] + $extraDays,
            'new_total' => round((float)$booking['total_amount'] + $extraCost, 2),
            'rate' => $rate,
        ];
    }

    public function create(array $booking, array $calc, string $reason): int
    {
        if ($this->value("SELECT id FROM booking_extensions WHERE booking_id=? AND status='pending'", [$booking['id']])) {
            throw new DomainException(t('This booking already has an extension request waiting for approval.'));
        }
        return $this->insert('booking_extensions', [
            'booking_id' => $booking['id'],
            'current_end_date' => $booking['end_date'],
            'requested_end_date' => $calc['new_end'],
            'extra_days' => $calc['extra_days'],
            'extra_cost' => $calc['extra_cost'],
            'reason' => mb_substr($reason, 0, 500),
        ]);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT x.*, b.booking_code, b.farmer_id, b.equipment_id, e.name equipment_name, u.name farmer_name, u.email farmer_email
            FROM booking_extensions x JOIN bookings b ON b.id=x.booking_id JOIN equipment e ON e.id=b.equipment_id JOIN users u ON u.id=b.farmer_id WHERE x.id=?', [$id]);
    }

    public function forBooking(int $bookingId): array
    {
        return $this->all('SELECT x.*, r.name reviewer_name FROM booking_extensions x LEFT JOIN users r ON r.id=x.reviewed_by WHERE x.booking_id=? ORDER BY x.id DESC', [$bookingId]);
    }

    public function pending(): array
    {
        return $this->all("SELECT x.*, b.booking_code, b.end_date, e.name equipment_name, u.name farmer_name
            FROM booking_extensions x JOIN bookings b ON b.id=x.booking_id JOIN equipment e ON e.id=b.equipment_id JOIN users u ON u.id=b.farmer_id
            WHERE x.status='pending' ORDER BY x.created_at");
    }

    /** Approve: re-check availability under lock, then extend the booking (extra cost becomes balance due). */
    public function approve(int $id, int $reviewerId, ?string $note): array
    {
        return $this->transaction(function () use ($id, $reviewerId, $note): array {
            $ext = $this->one("SELECT * FROM booking_extensions WHERE id=? AND status='pending' FOR UPDATE", [$id]);
            if (!$ext) {
                throw new DomainException(t('This extension has already been reviewed.'));
            }
            $this->one('SELECT id FROM equipment WHERE id=(SELECT equipment_id FROM bookings WHERE id=?) FOR UPDATE', [$ext['booking_id']]);
            $booking = $this->one('SELECT * FROM bookings WHERE id=? FOR UPDATE', [$ext['booking_id']]);
            if ($booking['end_date'] !== $ext['current_end_date']) {
                throw new DomainException(t('The booking dates changed after this request. Please ask the farmer to submit a new request.'));
            }
            $calc = $this->calculate($booking, $ext['requested_end_date']);
            $this->run('UPDATE bookings SET end_date=?, days=?, rental_cost=rental_cost+?, total_amount=total_amount+? WHERE id=?',
                [$calc['new_end'], $calc['new_days'], $calc['extra_cost'], $calc['extra_cost'], $booking['id']]);
            $this->run("UPDATE booking_extensions SET status='approved', reviewed_by=?, reviewed_at=NOW(), response_note=?, extra_cost=?, extra_days=? WHERE id=?",
                [$reviewerId, $note, $calc['extra_cost'], $calc['extra_days'], $id]);
            return $ext + ['calc' => $calc];
        });
    }

    public function reject(int $id, int $reviewerId, ?string $note): array
    {
        $ext = $this->one("SELECT * FROM booking_extensions WHERE id=? AND status='pending'", [$id]);
        if (!$ext) {
            throw new DomainException(t('This extension has already been reviewed.'));
        }
        $this->run("UPDATE booking_extensions SET status='rejected', reviewed_by=?, reviewed_at=NOW(), response_note=? WHERE id=?", [$reviewerId, $note, $id]);
        return $ext;
    }
}
