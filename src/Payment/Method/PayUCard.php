<?php

namespace Snowdog\Hyva\Checkout\PayU\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\GetUserPayMethods;
use Magento\Checkout\Model\Session as SessionCheckout;
use PayU\PaymentGateway\Model\Ui\CardConfigProvider;

class PayUCard extends Component implements EvaluationInterface
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
        $methods = $this->getMethods->execute('test@example.com', 2);
        foreach ($methods['cardTokens'] as $method) {
            $this->methods[] = [
                'value' => $method->value,
                'brandImageUrl' => $method->brandImageUrl,
                'cardBrand' => $method->cardBrand,
                'cardNumberMasked' => $method->cardNumberMasked,
            ];
        }
        if (count($this->methods) == 0) {
            $this->showCardForm = true;
        }
    }

    public function showForm()
    {
        $this->method = '';
        $this->showCardForm = true;
        $this->dispatchBrowserEvent('payu_card:toggle:form', true);
    }

    public function selectSaved(string $token)
    {
        $this->method = $token;
        $this->showCardForm = false;
        $this->dispatchBrowserEvent('payu_card:toggle:form', false);
    }

    public function token(string $token)
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $token);
        $quote->getPayment()->setAdditionalInformation(
            PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
            PayUConfigInterface::PAYU_CC_TRANSFER_KEY,
        );
        $this->quoteRepository->save($quote);
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        return $resultFactory->createSuccess();

        if ($this->sessionCheckout->getQuote()->getPayment()->getMethod() != 'payu_gateway_card') {
            return $resultFactory->createSuccess();
        }

        if (empty($this->method)) {
            return $resultFactory->createBlocking(__('Payment method not selected'));
        }

        if (!$this->acceptTos) {
            return $resultFactory->createBlocking(__('TOS not accepted'));
        }

        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PayUConfigInterface::PAYU_METHOD_CODE, $this->method);
        $quote->getPayment()->setAdditionalInformation(
            PayUConfigInterface::PAYU_METHOD_TYPE_CODE,
            PayUConfigInterface::PAYU_BANK_TRANSFER_KEY
        );
        $this->quoteRepository->save($quote);

        return $resultFactory->createSuccess();
    }
}