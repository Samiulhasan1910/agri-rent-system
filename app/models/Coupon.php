<?php
declare(strict_types=1);

class Coupon extends Model
{
    /**
     * Validates a coupon for an amount.
     * @return array{coupon: array, discount: float}
     * @throws DomainException "Invalid or expired coupon code."
     */
    public function validate(string $code, float $amount): array
    {
        $coupon = $this->one('SELECT * FROM coupons WHERE code=?', [mb_strtoupper(trim($code))]);
        $invalid = t('Invalid or expired coupon code.');
        if (!$coupon || $coupon['status'] !== 'active') {
            throw new DomainException($invalid);
        }
        if (($coupon['starts_at'] && $coupon['starts_at'] > today()) || ($coupon['expires_at'] && $coupon['expires_at'] < today())) {
            throw new DomainException($invalid);
        }
        if ($coupon['usage_limit'] !== null && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            throw new DomainException($invalid);
        }
        if ($amount < (float)$coupon['min_amount']) {
            throw new DomainException(t('This coupon requires a minimum amount of :amount.', ['amount' => money($coupon['min_amount'])]));
        }
        $discount = $coupon['discount_type'] === 'percent'
            ? round($amount * (float)$coupon['discount_value'] / 100, 2)
            : (float)$coupon['discount_value'];
        if ($coupon['max_discount'] !== null) {
            $discount = min($discount, (float)$coupon['max_discount']);
        }
        $discount = min($discount, $amount);
        return ['coupon' => $coupon, 'discount' => round($discount, 2)];
    }

    public function used(int $id): void
    {
        $this->run('UPDATE coupons SET used_count=used_count+1 WHERE id=?', [$id]);
    }

    public function listAll(): array
    {
        return $this->all('SELECT * FROM coupons ORDER BY status, expires_at DESC');
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT * FROM coupons WHERE id=?', [$id]);
    }

    public function codeExists(string $code, int $exceptId = 0): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM coupons WHERE code=? AND id<>?', [mb_strtoupper($code), $exceptId]);
    }

    public function save(array $data, int $id = 0): int
    {
        if ($id) {
            $this->update('coupons', $id, $data);
            return $id;
        }
        return $this->insert('coupons', $data);
    }

    public function remove(int $id): void
    {
        $this->run('DELETE FROM coupons WHERE id=?', [$id]);
    }
}
