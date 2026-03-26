<?php

namespace Omnipay\WGOP\Message;

use Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;

abstract class AbstractRequest extends OmnipayAbstractRequest
{
    // Base URLs — override via WGOP_BASE_URL / WGOP_TEST_BASE_URL env vars if needed
    const PROD_BASE_URL = 'https://payment.anzworldline-solutions.com.au';
    const TEST_BASE_URL = 'https://payment.preprod.anzworldline-solutions.com.au';

    // -------------------------------------------------------------------------
    // Parameter accessors (passed down from Gateway)
    // -------------------------------------------------------------------------

    public function getMerchantId(): string
    {
        return $this->getParameter('merchantId') ?? '';
    }

    public function setMerchantId(string $value): static
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getApiKeyId(): string
    {
        return $this->getParameter('apiKeyId') ?? '';
    }

    public function setApiKeyId(string $value): static
    {
        return $this->setParameter('apiKeyId', $value);
    }

    public function getApiSecretKey(): string
    {
        return $this->getParameter('apiSecretKey') ?? '';
    }

    public function setApiSecretKey(string $value): static
    {
        return $this->setParameter('apiSecretKey', $value);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function getBaseUrl(): string
    {
        if ($this->getTestMode()) {
            return rtrim(getenv('WGOP_TEST_BASE_URL') ?: self::TEST_BASE_URL, '/');
        }

        return rtrim(getenv('WGOP_BASE_URL') ?: self::PROD_BASE_URL, '/');
    }

    /**
     * Build the GCS v1HMAC Authorization header value.
     *
     * Signing string format (each line terminated with \n):
     *   HTTP_METHOD
     *   Content-Type
     *   Date
     *   [x-gcs-* headers, lowercase key:value, sorted alphabetically — one per line]
     *   Request URI (path + query string, no host)
     *
     * Signature = base64(HMAC-SHA256(apiSecretKey, signingString))
     * Header    = "GCS v1HMAC:{apiKeyId}:{signature}"
     */
    protected function buildAuthorizationHeader(
        string $method,
        string $requestUri,
        string $contentType,
        string $date,
        array  $xGcsHeaders = []
    ): string {
        $signingString = strtoupper($method) . "\n"
            . $contentType . "\n"
            . $date . "\n";

        // Include any x-gcs-* headers sorted alphabetically
        ksort($xGcsHeaders);
        foreach ($xGcsHeaders as $name => $value) {
            $signingString .= strtolower($name) . ':' . trim($value) . "\n";
        }

        $signingString .= $requestUri . "\n";

        $signature = base64_encode(
            hash_hmac('sha256', $signingString, $this->getApiSecretKey(), true)
        );

        return sprintf('GCS v1HMAC:%s:%s', $this->getApiKeyId(), $signature);
    }
}
