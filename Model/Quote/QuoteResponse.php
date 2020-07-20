<?php
namespace Lmerchant\Checkout\Model\Quote;

use Magento\Framework\DataObject;
use Lmerchant\Checkout\Api\Data\QuoteResponseInterface;

class QuoteResponse extends DataObject implements QuoteResponseInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const ORDER_REFERENCE = 'orderReference';
    const RESULT = 'result';

    /**
     * @return boolean|null
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * @param boolean $success
     * @return $this
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
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
    public function getOrderReference()
    {
        return $this->getData(self::ORDER_REFERENCE);
    }

    /**
     * @param string $orderReference
     * @return $this
     */
    public function setOrderReference($orderReference)
    {
        return $this->setData(self::ORDER_REFERENCE, $orderReference);
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
}
