<?php

namespace App\Models;

/**
 * Payment methods offered at the counter come from Master Lists (type "payment_method",
 * managed by the admin). Online gateways (Stripe, Razorpay...) are system integrations
 * with their own codes; each screen passes the gateways it supports.
 */
class PaymentMethod
{
    const TYPE = 'payment_method';

    /**
     * Admin methods first, then the given gateways (code => name).
     */
    public static function options(array $gateways = [], $currentCode = null): array
    {
        return MasterListItem::options(self::TYPE, $currentCode) + $gateways;
    }

    /**
     * Name of a stored code: an admin method, otherwise a gateway or legacy name.
     */
    public static function label($code, array $otherNames = []): ?string
    {
        return MasterListItem::label(self::TYPE, $code, $otherNames[$code] ?? null);
    }

    /**
     * Paid at the counter (an admin method), as opposed to an online gateway.
     */
    public static function isOffline($code): bool
    {
        return $code !== null && $code !== '' && array_key_exists((int) $code, MasterListItem::labels(self::TYPE));
    }
}
