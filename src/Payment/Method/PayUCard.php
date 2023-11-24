<?php

namespace Snowdog\Hyva\Checkout\PayU\Payment\Method;

use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\GetUserPayMethods;
use Magento\Checkout\Model\Session as SessionCheckout;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;

class PayUCard extends Component
{
    public string $method = '';

    public bool $acceptTos = true;

    public bool $showCardForm = false;

    public array $methods = [];

    public function __construct(
        private readonly GetUserPayMethods       $getMethods,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly GatewayConfig           $gatewayConfig,
        private readonly SessionCheckout         $sessionCheckout
    ) {
    }

    public function mount()
    {
        $this->gatewayConfig->setMethodCode(CardConfigProvider::CODE);
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        if (
            isset($data[PayUConfigInterface::PAYU_METHOD_CODE], $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE])
            && $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE] == PayUConfigInterface::PAYU_CC_TRANSFER_KEY
        ) {
            $this->method = $data[PayUConfigInterface::PAYU_METHOD_CODE];
        }
        if (
            isset($data[PayUConfigInterface::PAYU_METHOD_CODE], $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE])
            && $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE] == PayUConfigInterface::PAYU_BANK_TRANSFER_KEY
            && $data[PayUConfigInterface::PAYU_METHOD_CODE] == 'c'
        ) {
            $this->method = 'c';
        }
        $methods = $this->getMethods->execute('test@example.com', 2);
        if (isset($methods['cardTokens'])) {
            foreach ($methods['cardTokens'] as $method) {
                $this->methods[] = [
                    'value' => $method->value,
                    'brandImageUrl' => $method->brandImageUrl,
                    'cardBrand' => $method->cardBrand,
                    'cardNumberMasked' => $method->cardNumberMasked,
                ];
            }
        }
        if (count($this->methods) == 0 || $this->method == 'c') {
            $this->showCardForm = true;
            $this->method = 'c';
            $quote = $this->sessionCheckout->getQuote();
            $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, 'c');
            $quote->getPayment()->setAdditionalInformation(
                PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
                PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
            );
            $this->quoteRepository->save($quote);
        }
    }

    public function showForm()
    {
        $this->method = 'c';
        $this->showCardForm = true;
        $this->dispatchBrowserEvent('payu_card:toggle:form', true);
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, 'c');
        $quote->getPayment()->setAdditionalInformation(
            PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
            PayUConfigInterface::PAYU_BANK_TRANSFER_KEY,
        );
        $this->quoteRepository->save($quote);
    }

    public function selectSaved(string $token)
    {
        $this->method = $token;
        $this->showCardForm = false;
        $this->dispatchBrowserEvent('payu_card:toggle:form', false);
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $token);
        $quote->getPayment()->setAdditionalInformation(
            PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
            PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
        );
        $this->quoteRepository->save($quote);
    }

    public function token(string $token)
    {
        $this->method = $token;
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $token);
        $quote->getPayment()->setAdditionalInformation(
            PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
            PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
        );
        $this->quoteRepository->save($quote);
    }
}