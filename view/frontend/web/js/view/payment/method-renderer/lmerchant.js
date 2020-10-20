/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
  "jquery",
  "Magento_Checkout/js/model/quote",
  "Magento_Checkout/js/view/payment/default",
  "mage/url",
  "Magento_Ui/js/model/messageList",
  "Magento_Customer/js/customer-data",
], function ($, quote, Component, mageUrl, globalMessageList, customerData) {
  "use strict";

  return Component.extend({
    defaults: {
      template: "Lmerchant_Checkout/payment/form",
      transactionResult: "",
    },

    initObservable: function () {
      this._super().observe(["transactionResult"]);
      return this;
    },

    getCode: function () {
      return "lmerchant";
    },

    getData: function () {
      return {
        method: this.item.method,
        additional_data: {
          transaction_result: this.transactionResult(),
        },
      };
    },

    getTransactionResults: function () {
      return _.map(
        window.checkoutConfig.payment.lmerchant.transactionResults,
        function (value, key) {
          return {
            value: key,
            transaction_result: value,
          };
        }
      );
    },

    completeOrder: function () {
      var url = mageUrl.build("lmerchant/payment/process");
      var data = $("#co-shipping-form").serialize();
      var email = window.checkoutConfig.customerData.email;

      var ajaxRedirected = false;

      if (!window.checkoutConfig.quoteData.customer_id) {
        email = document.getElementById("customer-email").value;
      }

      data = data + "&cartId=" + quote.getQuoteId() + "&email=" + email;

      $.ajax({
        url: url,
        method: "post",
        data: data,
        beforeSend: function () {
          $("body").trigger("processStart");
        },
      })
        .done(function (response) {
          console.log({ response });
          var data = response;

          var redirectToPortal = function (paymentRequest) {
            var form = document.createElement("form");

            form.method = "POST";
            form.action = paymentRequest.url;
            form.style.display = "none";

            Object.keys(paymentRequest).forEach((key) => {
              if (key === "url" || key === "success") {
                return;
              }

              var elem = document.createElement("input");
              elem.name = key;
              elem.value = paymentRequest[key];
              form.appendChild(elem);
            });

            document.body.appendChild(form);

            form.submit();
          };

          if (
            typeof data.success !== "undefined" &&
            typeof data.url !== "undefined" &&
            data.success
          ) {
            $("body").ajaxStop(function () {
              ajaxRedirected = true;
              redirectToPortal(data);
            });
            setTimeout(function () {
              if (!ajaxRedirected) {
                redirectToPortal(data);
              }
            }, 5000);

            return;
          }

          if (
            typeof data.error !== "undefined" &&
            typeof data.message !== "undefined" &&
            data.error &&
            data.message.length
          ) {
            globalMessageList.addErrorMessage({
              message: data.message,
            });

            return;
          }
        })
        .fail(function (xhr, status ) {
          console.error("request failed with status " + status); 
          globalMessageList.addErrorMessage({
            message: "We could not process this request at this time, please try again or select other payment method",
          });
        })
        .always(function () {
          customerData.invalidate(["cart"]);
          $("body").trigger("processStop");
        });
    },
  });
});
