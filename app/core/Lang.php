<?php
declare(strict_types=1);

/**
 * English/Bangla translation. English text is the key; app/lang/bn.php maps it to Bangla.
 * Placeholders use :name syntax.
 */
final class Lang
{
    private static string $locale = 'en';
    private static ?array $dictionary = null;

    public static function set(string $locale): void
    {
        self::$locale = in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public static function get(): string
    {
        return self::$locale;
    }

    public static function dictionary(): array
    {
        if (self::$dictionary === null) {
            $file = APP_PATH . '/lang/bn.php';
            self::$dictionary = is_file($file) ? require $file : [];
        }
        return self::$dictionary;
    }

    public static function translate(string $key, array $params = []): string
    {
        $text = $key;
        if (self::$locale === 'bn') {
            $dict = self::dictionary();
            $text = $dict[$key] ?? $key;
        }
        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string)$value, $text);
        }
        return $text;
    }

    /** Converts western digits to Bangla digits when the Bangla UI is active. */
    public static function digits(string $value): string
    {
        return self::$locale === 'bn' ? strtr($value, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']) : $value;
    }
}
