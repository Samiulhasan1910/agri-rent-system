<?php
declare(strict_types=1);

/**
 * Seasonal pricing rules:
 *   Harvest season   -> base price + harvest_increase_percent
 *   Off-season       -> base price - off_season_discount_percent
 *   Normal / unknown -> base price
 * The final price is never below min_rental_price and never zero or negative.
 */
final class Pricing
{
    public const SEASONS = ['normal', 'harvest', 'off_season'];

    public static function seasonFor(?string $date = null): string
    {
        if (Settings::get('season_mode', 'auto') === 'manual') {
            $season = (string)Settings::get('current_season', 'normal');
            return in_array($season, self::SEASONS, true) ? $season : 'normal';
        }
        $month = (int)date('n', $date ? (strtotime($date) ?: time()) : time());
        if (in_array($month, self::months('harvest_months'), true)) {
            return 'harvest';
        }
        if (in_array($month, self::months('off_season_months'), true)) {
            return 'off_season';
        }
        return 'normal';
    }

    public static function percent(string $season): float
    {
        if ($season === 'harvest') {
            return max(0, (float)Settings::get('harvest_increase_percent', 20));
        }
        if ($season === 'off_season') {
            return -min(90, max(0, (float)Settings::get('off_season_discount_percent', 10)));
        }
        return 0.0;
    }

    /**
     * @return array{base: float, season: string, percent: float, price: float}
     * @throws DomainException when the base price is missing
     */
    public static function quote($basePrice, ?string $date = null): array
    {
        if ($basePrice === null || $basePrice === '' || !is_numeric($basePrice) || (float)$basePrice <= 0) {
            throw new DomainException(t('Base price is missing for this equipment. Price cannot be calculated.'));
        }
        $base = round((float)$basePrice, 2);
        $season = self::seasonFor($date);
        $percent = self::percent($season);
        $price = round($base * (1 + $percent / 100), 2);
        // Never below the configured minimum, and never zero/negative.
        $price = max($price, max(1.0, (float)Settings::get('min_rental_price', 100)));
        return ['base' => $base, 'season' => $season, 'percent' => $percent, 'price' => $price];
    }

    /** Safe variant for listings: returns null instead of throwing. */
    public static function tryQuote($basePrice, ?string $date = null): ?array
    {
        try {
            return self::quote($basePrice, $date);
        } catch (DomainException $e) {
            return null;
        }
    }

    public static function label(string $season): string
    {
        $percent = self::percent($season);
        if ($season === 'harvest') {
            return t('Harvest season +:p%', ['p' => rtrim(rtrim(number_format($percent, 1), '0'), '.')]);
        }
        if ($season === 'off_season') {
            return t('Off-season -:p%', ['p' => rtrim(rtrim(number_format(abs($percent), 1), '0'), '.')]);
        }
        return t('Normal season');
    }

    private static function months(string $key): array
    {
        $months = array_map('intval', array_filter(array_map('trim', explode(',', (string)Settings::get($key, '')))));
        return array_values(array_filter($months, static fn($m) => $m >= 1 && $m <= 12));
    }
}
