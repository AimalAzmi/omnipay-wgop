<?php

namespace Omnipay\WGOP;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\WGOP\Message\PurchaseRequest;
use Omnipay\WGOP\Message\RefundRequest;

/**
 * ANZ Worldline Global Online Pay (WGOP) Gateway
 *
 * Credentials map to gateway_accounts columns:
 *   merchant_id      => merchantId    (PSP ID / Merchant ID, used in URL path)
 *   merchant_key     => apiKeyId      (API Key ID, used in Authorization header)
 *   merchant_private => apiSecretKey  (API Secret Key, used to sign requests)
 */
class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'WGOP';
    }

    public function getDefaultParameters(): array
    {
        return [
            'merchantId'   => '',
            'apiKeyId'     => '',
            'apiSecretKey' => '',
            'testMode'     => false,
        ];
    }

    // -------------------------------------------------------------------------
    // Getters / Setters
    // -------------------------------------------------------------------------

    public function getMerchantId(): string
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId(string $value): static
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getApiKeyId(): string
    {
        return $this->getParameter('apiKeyId');
    }

    public function setApiKeyId(string $value): static
    {
        return $this->setParameter('apiKeyId', $value);
    }

    public function getApiSecretKey(): string
    {
        return $this->getParameter('apiSecretKey');
    }

    public function setApiSecretKey(string $value): static
    {
        return $this->setParameter('apiSecretKey', $value);
    }

    // -------------------------------------------------------------------------
    // Supported operations
    // -------------------------------------------------------------------------

    public function purchase(array $parameters = []): RequestInterface
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    public function refund(array $parameters = []): RequestInterface
    {
        return $this->createRequest(RefundRequest::class, $parameters);
    }
}
