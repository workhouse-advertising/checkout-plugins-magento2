<?php
namespace Lmerchant\Checkout\Api\Data;

interface QuoteRequestInterface
{
    /**
     * @return string|null
     */
    public function getMerchantId();

    /**
     * @param string $merchantId
     * @return $this
     */
    public function setMerchantId($merchantId);

    /**
     * @return string|null
     */
    public function getAmount();

    /**
     * @param string $merchantId
     * @return $this
     */
    public function setAmount($amount);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @return string|null
     */
    public function getMerchantReference();

    /**
     * @param string $merchantReference
     * @return $this
     */
    public function setMerchantReference($merchantReference);

    /**
     * @return string|null
     */
    public function getGatewayReference();

    /**
     * @param string $gatewayReference
     * @return $this
     */
    public function setGatewayReference($gatewayReference);

    /**
    * @return string|null
    */
    public function getPromotionReference();

    /**
     * @param string $promotionReference
     * @return $this
     */
    public function setPromotionReference($promotionReference);

    /**
     * @return string|null
     */
    public function getResult();

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result);

    /**
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return string|null
     */
    public function getSignature();

    /**
     * @param string $signature
     * @return $this
     */
    public function setSignature($signature);
}
