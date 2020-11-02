define([
  "uiComponent",
  "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
  "use strict";
  rendererList.push({
    type: "latitude",
    component: "Latitude_Checkout/js/view/payment/method-renderer/latitude",
  });
  /** Add view logic here if needed */
  return Component.extend({});
});
