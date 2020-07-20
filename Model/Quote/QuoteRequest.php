<?php
namespace Lmerchant\Checkout\Model\Quote;

use Magento\Framework\DataObject;
use Lmerchant\Checkout\Api\Data\QuoteRequestInterface;

class QuoteRequest extends DataObject implements QuoteRequestInterface
{
    const MERCHANT_ID = 'merchantId';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const MERCHANT_REFERENCE = 'merchantReference';
    const GATEWAY_REFERENCE = 'gatewayReference';
    const PROMOTION_REFERENCE = 'promotionReference';
    const RESULT = 'result';
    const MESSAGE = 'message';
    const SIGNATURE = 'signature';

    /**
     * @return string|null
     */
    public function getMerchantId()
    {
        return $this->getData(self::MERCHANT_ID);
    }

    /**
     * @param string $merchantId
     * @return $this
     */
    public function setMerchantId($merchantId)
    {
        return $this->setData(self::MERCHANT_ID, $merchantId);
    }

    /**
     * @return string|null
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @param string $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @return string|null
     */
    public function getMerchantReference()
    {
        return $this->getData(self::MERCHANT_REFERENCE);
    }

    /**
     * @param string $merchantReference
     * @return $this
     */
    public function setMerchantReference($merchantReference)
    {
        return $this->setData(self::MERCHANT_REFERENCE, $merchantReference);
    }

    /**
     * @return string|null
     */
    public function getGatewayReference()
    {
        return $this->getData(self::GATEWAY_REFERENCE);
    }

    /**
     * @param string $gatewayReference
     * @return $this
     */
    public function setGatewayReference($gatewayReference)
    {
        return $this->setData(self::GATEWAY_REFERENCE, $gatewayReference);
    }

    /**
    * @return string|null
    */
    public function getPromotionReference()
    {
        return $this->getData(self::PROMOTION_REFERENCE);
    }

    /**
     * @param string $promotionReference
     * @return $this
     */
    public function setPromotionReference($promotionReference)
    {
        return $this->setData(self::PROMOTION_REFERENCE, $promotionReference);
    }

    /**
     * @return string|null
     */
    public function getResult()
    {
        return $this->getData(self::RESULT);
    }

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result)
    {
        return $this->setData(self::RESULT, $result);
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @return string|null
     */
    public function getSignature()
    {
        return $this->getData(self::SIGNATURE);
    }

    /**
     * @param string $signature
     * @return $this
     */
    public function setSignature($signature)
    {
        return $this->setData(self::SIGNATURE, $signature);
    }
}
