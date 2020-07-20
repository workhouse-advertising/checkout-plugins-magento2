<?php
namespace Lmerchant\Checkout\Api\Data;

interface QuoteResponseInterface
{
    /**
     * @return boolean|null
     */
    public function getSuccess();

    /**
     * @param boolean $success
     * @return $this
     */
    public function setSuccess($success);

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
    public function getOrderReference();

    /**
     * @param string $orderReference
     * @return $this
     */
    public function setOrderReference($orderReference);

    /**
     * @return string|null
     */
    public function getResult();

    /**
     * @param string $result
     * @return $this
     */
    public function setResult($result);
}
