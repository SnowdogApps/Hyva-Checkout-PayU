# Hyvä Checkout PayU

Hyvä Magewire Checkout compatibility module for `payu/magento24-payment-gateway`

## Hyvä Checkout

Hyvä Magewire Checkout is flexible and highly configurable checkout replacement for Magento 2.
See more at [hyva.io](https://www.hyva.io/hyva-checkout.html)

## PayU Payment Gateway

This module extends original Magento2 module for [PayU](https://poland.payu.com/) to be able to use it within Hyvä Magewire Checkout.
See original module at [PayU-EMEA/plugin_magento_24](https://github.com/PayU-EMEA/plugin_magento_24/)

## Functionality support matrix

| Functionality                 | Support Status       |
|-------------------------------|----------------------|
| Bank transfer payments        | :white_check_mark:   |
| Card paymnets (hosted fields) | :white_check_mark:   |
| Saved cards                   | :white_check_mark:   |
| Repay (pay again)             | :x:                  |
| Credit Card Currency Rates    | :warning: not tested |

## Notes

* Credit card form (hosted fields) is not part of payment method block, but it is added after block with payment methods. 
For visual effect You want to have PayU Cards payment method as last payment method (so there is nothing in between payment block and card form)
* If Credit card tokenization fails on checkout page customer is redirected to PayU to see credit card form there.