<?php

namespace Omnipay\WGOP\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * WGOP Refund Response
 *
 * HTTP 201 → refund created
 * Success statuses: REFUND_REQUESTED, REFUNDED
 */
class RefundResponse extends AbstractResponse
{
    const SUCCESSFUL_STATUSES = [
        'REFUND_REQUESTED',
        'REFUNDED',
    ];

    protected int $statusCode;

    public function __construct(RequestInterface $request, array $data, int $statusCode = 200)
    {
        parent::__construct($request, $data);
        $this->statusCode = $statusCode;
    }

    public function isSuccessful(): bool
    {
        $status = $this->data['status'] ?? null;

        return $status !== null && in_array($status, self::SUCCESSFUL_STATUSES);
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function getTransactionReference(): ?string
    {
        return $this->data['id'] ?? null;
    }

    public function getMessage(): ?string
    {
        if (!empty($this->data['errors'])) {
            $first = $this->data['errors'][0];
            return $first['message'] ?? $first['id'] ?? 'Unknown error';
        }

        return $this->data['status'] ?? null;
    }

    public function getHttpStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
