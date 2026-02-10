<?php
namespace PBay\Helpers;

/**
 * ADA/USD price helper with CoinGecko caching
 */
class PriceHelper {

    /**
     * Get current ADA price in USD (cached 5 min)
     */
    public static function getAdaPrice() {
        return AnvilAPI::getAdaPrice();
    }

    /**
     * Convert USD to ADA
     */
    public static function usdToAda($usd_amount) {
        $ada_price = self::getAdaPrice();
        if ($ada_price <= 0) {
            return 0;
        }
        return round($usd_amount / $ada_price, 6);
    }

    /**
     * Convert ADA to USD
     */
    public static function adaToUsd($ada_amount) {
        $ada_price = self::getAdaPrice();
        return round($ada_amount * $ada_price, 2);
    }

    /**
     * Format ADA amount for display
     */
    public static function formatAda($ada_amount) {
        return number_format($ada_amount, 2) . ' ADA';
    }

    /**
     * Format USD amount for display
     */
    public static function formatUsd($usd_amount) {
        return '$' . number_format($usd_amount, 2);
    }

    /**
     * Get exchange rate info for display
     */
    public static function getExchangeRateInfo() {
        $ada_price = self::getAdaPrice();
        return [
            'ada_usd' => $ada_price,
            'formatted' => '$' . number_format($ada_price, 4) . '/ADA',
            'cached' => (get_transient('pbay_ada_price') !== false),
        ];
    }
}
