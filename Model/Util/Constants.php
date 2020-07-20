<?php
namespace Lmerchant\Checkout\Model\Util;

class Constants
{
    const METHOD_GUEST = 'guest';
    const METHOD_CODE = 'lmerchant';
    const MINUTE_DELAYED_ORDER = 75;

    const CANCEL_ROUTE = 'checkout/cart';
    const CALLBACK_ROUTE = 'rest/V1/lmerchant/quote';
    const SUCCESS_ROUTE = 'checkout/onepage/success';
    const COMPLETE_ROUTE = 'lmerchant/payment/complete';

    const CART_ID= 'cart_id';
    const GATEWAY_REFERENCE = 'gateway_reference';
    const PAYMENT_STATUS = 'payment_status';
    const PROMOTION_REFERENCE = 'promotion';
}
