<?php

namespace Snowdog\Hyva\Checkout\PayU\Block;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use PayU\PaymentGateway\Model\Config;

class Script implements ArgumentInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function getPosId(): int
    {
        return $this->config->getMerchantPosiId();
    }

    public function getLanguage(): string
    {
        return 'en';
    }

    public function getSDKUrl(): string
    {
        return 'https://secure.snd.payu.com/javascript/sdk';
    }
}