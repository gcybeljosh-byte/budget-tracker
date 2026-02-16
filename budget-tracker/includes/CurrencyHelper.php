<?php
// includes/CurrencyHelper.php

class CurrencyHelper {
    private static $symbols = [
        'PHP' => '₱',
        'USD' => '$',
        'EUR' => '€'
    ];

    private static $locales = [
        'PHP' => 'en-PH',
        'USD' => 'en-US',
        'EUR' => 'de-DE'
    ];

    public static function getSymbol($code) {
        return self::$symbols[$code] ?? '₱';
    }

    public static function format($amount, $code) {
        $symbol = self::getSymbol($code);
        return $symbol . number_format($amount, 2);
    }

    public static function getJSConfig($code) {
        return [
            'code' => $code,
            'symbol' => self::getSymbol($code),
            'locale' => self::$locales[$code] ?? 'en-PH'
        ];
    }
}
?>
