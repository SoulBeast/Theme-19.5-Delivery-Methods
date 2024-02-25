define(
    [],
    function () {
        "use strict";
        return {
            getRules: function () {
                return {
                    'country_id': {
                        'required': true
                    },
                    'city': {
                        'required': true
                    }
                };
            },
        };
    }
);

/* define(['Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';
    return function () {
        var shippingAddress = quote.shippingAddress(); if (shippingAddress) {
            var company = shippingAddress.company; console.log('Company: ', company);
            // Здесь вы можете выполнить любые операции с переменной "company"
        }
        };
    }); */