<?php
declare(strict_types=1);

class Inspection extends Model
{
    public const CONDITIONS = ['good' => 'Good', 'damaged' => 'Damaged', 'needs_service' => 'Needs Service'];

    /**
     * Saves a return inspection atomically: inspection record, booking completion,
     * equipment availability (damaged => unavailable) and usage hours.
     */
    public function record(int $bookingId, string $condition, string $comments, ?float $hours, int $inspectorId): array
    {
        return $this->transaction(function () use ($bookingId, $condition, $comments, $hours, $inspectorId): array {
            $booking = $this->one("SELECT b.*, e.name equipment_name FROM bookings b JOIN equipment e ON e.id=b.equipment_id WHERE b.id=? FOR UPDATE", [$bookingId]);
            if (!$booking || $booking['status'] !== 'returned') {
                throw new DomainException(t('Only returned rentals can be inspected.'));
            }
            if ($this->value('SELECT id FROM inspections WHERE booking_id=?', [$bookingId])) {
                throw new DomainException(t('This rental has already been inspected.'));
            }
            $equipmentId = (int)$booking['equipment_id'];
            $this->insert('inspections', [
                'booking_id' => $bookingId,
                'equipment_id' => $equipmentId,
                'condition' => $condition,
                'comments' => $comments !== '' ? $comments : null,
                'usage_hours' => $hours,
                'inspected_by' => $inspectorId,
            ]);
            $this->run("UPDATE bookings SET status='completed' WHERE id=?", [$bookingId]);

            if ($condition === 'damaged') {
                $this->run("UPDATE equipment SET status='unavailable' WHERE id=? AND status='available'", [$equipmentId]);
                Health::alert($equipmentId, 'damaged', t(':name was returned damaged (:code). :comment', ['name' => $booking['equipment_name'], 'code' => $booking['booking_code'], 'comment' => $comments]));
            } elseif ($condition === 'needs_service') {
                Health::alert($equipmentId, 'service_due', t(':name needs service after return (:code). :comment', ['name' => $booking['equipment_name'], 'code' => $booking['booking_code'], 'comment' => $comments]));
                $this->run('UPDATE equipment SET hours_since_service=GREATEST(hours_since_service, usage_threshold_hours) WHERE id=?', [$equipmentId]);
            }
            Health::recordUsage($equipmentId, $bookingId, $hours, $inspectorId, 'return', $comments !== '' ? mb_substr($comments, 0, 255) : null);
            return $booking;
        });
    }

    public function history(int $limit = 30): array
    {
        return $this->all('SELECT i.*, e.name equipment_name, b.booking_code, u.name inspector_name, f.name farmer_name
            FROM inspections i JOIN equipment e ON e.id=i.equipment_id JOIN bookings b ON b.id=i.booking_id
            JOIN users u ON u.id=i.inspected_by JOIN users f ON f.id=b.farmer_id ORDER BY i.id DESC LIMIT ' . (int)$limit);
    }

    public function forBooking(int $bookingId): ?array
    {
        return $this->one('SELECT i.*, u.name inspector_name FROM inspections i JOIN users u ON u.id=i.inspected_by WHERE i.booking_id=?', [$bookingId]);
    }
}
