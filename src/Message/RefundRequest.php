<?php

namespace Omnipay\WGOP\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * WGOP Refund Request
 *
 * POST /v2/{merchantId}/payments/{paymentId}/refund
 *
 * Required parameters:
 *   transactionReference  — the WGOP payment ID returned by PurchaseResponse::getTransactionReference()
 *   amount                — float amount to refund (can be partial)
 *   currency              — ISO 4217
 */
class RefundRequest extends AbstractRequest
{
    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validate('transactionReference', 'amount', 'currency');

        return [
            'amountOfMoney' => [
                'amount'       => $this->getAmountInteger(),
                'currencyCode' => strtoupper($this->getCurrency()),
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function sendData(mixed $data): RefundResponse
    {
        $merchantId  = $this->getMerchantId();
        $paymentId   = $this->getTransactionReference();
        $requestUri  = "/v2/{$merchantId}/payments/{$paymentId}/refund";
        $url         = $this->getBaseUrl() . $requestUri;
        $contentType = 'application/json';
        $date        = gmdate('D, d M Y H:i:s') . ' GMT';
        $body        = json_encode($data);

        $authHeader = $this->buildAuthorizationHeader('POST', $requestUri, $contentType, $date);

        $httpResponse = $this->httpClient->request(
            'POST',
            $url,
            [
                'Authorization' => $authHeader,
                'Content-Type'  => $contentType,
                'Date'          => $date,
            ],
            $body
        );

        $statusCode   = $httpResponse->getStatusCode();
        $responseData = json_decode((string)$httpResponse->getBody(), true) ?? [];

        return $this->response = new RefundResponse($this, $responseData, $statusCode);
    }
}
