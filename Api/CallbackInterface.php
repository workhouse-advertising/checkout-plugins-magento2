<?php
namespace Latitude\Checkout\Api;

interface CallbackInterface
{
    /**
     * POST /v1/latitude/callback
     * @param string $merchantId
     * @param string $amount
     * @param string $currency,
     * @param string $merchantReference,
     * @param string $gatewayReference,
     * @param string $promotionReference,
     * @param string $result,
     * @param string $transactionType,
     * @param string $test,
     * @param string $message,
     * @param string $timestamp,
     * @param string $signature
     * @return string
     */
    public function handle(
        string $merchantId,
        string $amount,
        string $currency,
        string $merchantReference,
        string $gatewayReference,
        string $promotionReference,
        string $result,
        string $transactionType,
        string $test,
        string $message,
        string $timestamp,
        string $signature
    );
}