define([
  "uiComponent",
  "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
  "use strict";
  rendererList.push({
    type: "lmerchant",
    component: "Lmerchant_Checkout/js/view/payment/method-renderer/lmerchant",
  });
  /** Add view logic here if needed */
  return Component.extend({});
});
