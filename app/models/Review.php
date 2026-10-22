<?php
declare(strict_types=1);

class Review extends Model
{
    public function create(int $bookingId, int $farmerId, int $equipmentId, int $rating, string $text): int
    {
        return $this->insert('reviews', [
            'booking_id' => $bookingId,
            'farmer_id' => $farmerId,
            'equipment_id' => $equipmentId,
            'rating' => $rating,
            'review' => $text,
        ]);
    }

    public function existsForBooking(int $bookingId): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM reviews WHERE booking_id=?', [$bookingId]);
    }

    public function forBooking(int $bookingId): ?array
    {
        return $this->one('SELECT * FROM reviews WHERE booking_id=?', [$bookingId]);
    }
}
