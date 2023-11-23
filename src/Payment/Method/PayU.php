<?php

namespace Snowdog\Hyva\Checkout\PayU\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\GetPayMethods;
use Magento\Checkout\Model\Session as SessionCheckout;

class PayU extends Component implements EvaluationInterface
{
    public string $method = '';

    public bool $acceptTos = true;

    public array $methods = [];

    public function __construct(
        private readonly GetPayMethods           $getMethods,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly SessionCheckout         $sessionCheckout
    ) {
    }

    public function mount()
    {
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        if (
            isset($data[PayUConfigInterface::PAYU_METHOD_CODE], $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE])
            && $data[PayUConfigInterface::PAYU_METHOD_TYPE_CODE] == PayUConfigInterface::PAYU_BANK_TRANSFER_KEY
        ) {
            $this->method = $data[PayUConfigInterface::PAYU_METHOD_CODE];
        }
        $methods = $this->getMethods->execute();
        foreach ($methods as $method) {
            $this->methods[] = json_decode(json_encode($method), true);
        }
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if ($this->sessionCheckout->getQuote()->getPayment()->getMethod() != 'payu_gateway') {
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