<?php

namespace Snowdog\Hyva\Checkout\PayU\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use OpenPayU_Configuration;
use PayU\PaymentGateway\Api\PayUConfigInterface;
use PayU\PaymentGateway\Model\Config;

class Script implements ArgumentInterface
{
    public function __construct(
        private readonly Config            $config,
        private readonly ResolverInterface $resolver,
        private readonly CustomerSession   $customerSession,
    ) {
        $this->config->setDefaultConfig('payu_gateway_card');
    }

    public function getPosId(): string
    {
        return $this->config->getMerchantPosiId();
    }

    public function getLanguage(): string
    {
        return current(explode('_', $this->resolver->getLocale()));
    }

    public function getSDKUrl(): string
    {
        return match (OpenPayU_Configuration::getEnvironment()) {
            PayUConfigInterface::ENVIRONMENT_SECURE => 'https://secure.payu.com/javascript/sdk',
            PayUConfigInterface::ENVIRONMENT_SANBOX => 'https://secure.snd.payu.com/javascript/sdk',
        };
    }

    public function canSaveCard(): string
    {
        return $this->config->isStoreCardEnable() && $this->customerSession->isLoggedIn();
    }
}