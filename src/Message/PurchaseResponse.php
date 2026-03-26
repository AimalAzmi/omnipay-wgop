<?php

namespace Omnipay\WGOP\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * WGOP Purchase Response
 *
 * HTTP 201  → payment created, check payment.status
 * HTTP 402  → payment declined by acquirer, body has paymentResult.payment.status
 * HTTP 400+ → platform/validation error, body has errors[]
 *
 * Success statuses (SALE mode):
 *   CAPTURED           — funds captured immediately
 *   CAPTURE_REQUESTED  — capture queued (treat as success, will settle)
 */
class PurchaseResponse extends AbstractResponse
{
    const SUCCESSFUL_STATUSES = [
        'CAPTURED',
        'CAPTURE_REQUESTED',
    ];

    protected int $statusCode;

    public function __construct(RequestInterface $request, array $data, int $statusCode = 200)
    {
        parent::__construct($request, $data);
        $this->statusCode = $statusCode;
    }

    public function isSuccessful(): bool
    {
        $paymentStatus = $this->getPaymentStatus();

        return $paymentStatus !== null && in_array($paymentStatus, self::SUCCESSFUL_STATUSES);
    }

    public function isRedirect(): bool
    {
        return false;
    }

    /**
     * The WGOP payment ID (e.g. "3254564310_0") — store this for refunds.
     */
    public function getTransactionReference(): ?string
    {
        return $this->data['payment']['id']
            ?? $this->data['paymentResult']['payment']['id']
            ?? null;
    }

    /**
     * Human-readable status or error message.
     */
    public function getMessage(): ?string
    {
        // Validation / platform error
        if (!empty($this->data['errors'])) {
            $first = $this->data['errors'][0];
            return $first['message'] ?? $first['id'] ?? 'Unknown error';
        }

        // Payment declined — the error body wraps the payment result
        if (!empty($this->data['paymentResult']['payment']['status'])) {
            return $this->data['paymentResult']['payment']['status'];
        }

        // Normal response
        return $this->getPaymentStatus();
    }

    /**
     * Raw payment status string from the API (e.g. "CAPTURED", "REJECTED").
     */
    public function getPaymentStatus(): ?string
    {
        return $this->data['payment']['status']
            ?? $this->data['paymentResult']['payment']['status']
            ?? null;
    }

    /**
     * Numeric HTTP status code from the API response.
     */
    public function getHttpStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Full raw response data — useful for logging.
     */
    public function getData(): array
    {
        return $this->data;
    }
}
