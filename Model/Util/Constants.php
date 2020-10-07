<?php
namespace Lmerchant\Checkout\Model\Util;

class Constants
{
    const METHOD_GUEST = 'guest';
    const METHOD_CODE = 'lmerchant';
    const MINUTE_DELAYED_ORDER = 75;

    const CANCEL_ROUTE = 'checkout/cart';
    const CALLBACK_ROUTE = 'rest/V1/lmerchant/callback';
    const SUCCESS_ROUTE = 'checkout/onepage/success';
    const COMPLETE_ROUTE = 'lmerchant/payment/complete';

    const CART_ID= 'cart_id';
    const GATEWAY_REFERENCE = 'gateway_reference';
    const PAYMENT_RESULT = 'payment_result';
    const PROMOTION_REFERENCE = 'promotion';

    const ALLOWED_CURRENCY = array("AUD", "NZD");

    const TRANSACTION_RESULT_COMPLETED = "completed";
    const TRANSACTION_RESULT_FAILED = "failed";
    const TRANSACTION_RESULT_PENDING = "pending";

    const TRANSACTION_TYPE_AUTH = "authorization";
    const TRANSACTION_TYPE_SALE = "sale";
    const TRANSACTION_TYPE_VOID = "void";
    const TRANSACTION_TYPE_REFUND = "refund";
}
