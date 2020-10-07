<?php
namespace Lmerchant\Checkout\Api;

interface CallbackInterface
{
     /**
     * POST /v1/lmerchant/callback
     * @param string $merchantId
     * @param string $amount
     * @param string $currency
     * @param string $merchantReference
     * @param string $gatewayReference
     * @param string $promotionReference
     * @param string $result
     * @param string $status
     * @param string $isTest
     * @param string $message
     * @param string $signature
     *
     * @return mixed
     */
    
    public function handle(
        string $merchantId,
        string $amount,
        string $currency,
        string $merchantReference,
        string $gatewayReference,
        string $promotionReference,
        string $result,
        string $status,
        string $isTest,
        string $message,
        string $signature
    );
}