<?php

namespace Snowdog\Hyva\Checkout\PayU\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\PaymentGateway\Api\PayUConfigInterface;

class PlaceOrderServiceProvider extends AbstractPlaceOrderService
{
    public function __construct(
        CartManagementInterface                   $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($cartManagement);
    }

    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation()[PayUConfigInterface::PAYU_REDIRECT_URI_CODE];
    }
}