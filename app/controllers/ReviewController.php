<?php
declare(strict_types=1);

class ReviewController extends Controller
{
    public function store(): void
    {
        $user = $this->role('farmer');
        $this->requirePost();
        $booking = (new Booking())->find($this->int('booking_id'));
        if (!$booking || (int)$booking['farmer_id'] !== (int)$user['id'] || $booking['status'] !== 'completed') {
            $this->fail(t('Only completed rentals can be reviewed.'));
        }
        $rating = $this->int('rating');
        $text = $this->input('review');
        $errors = [];
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = t('Rating selection required.');
        }
        if ($text === '') {
            $errors['review'] = t('Review description cannot be empty.');
        } elseif (mb_strlen($text) > 1000) {
            $errors['review'] = t('Review is too long (max 1000 characters).');
        }
        if ($errors) {
            $this->fail(reset($errors), $errors);
        }
        $reviews = new Review();
        if ($reviews->existsForBooking((int)$booking['id'])) {
            $this->fail(t('You have already reviewed this rental.'));
        }
        try {
            $reviews->create((int)$booking['id'], (int)$user['id'], (int)$booking['equipment_id'], $rating, $text);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->fail(t('Review submission failed.'));
        }
        $this->success(t('Review submission successful.'), null, [
            'html' => '<div class="review-done">' . stars($rating, false) . '<p>' . e($text) . '</p></div>',
        ]);
    }
}
