<?php

namespace Omnipay\WGOP\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * WGOP Purchase Request
 *
 * Fires a POST /v2/{merchantId}/payments with authorizationMode=SALE.
 * 3DS is unconditionally skipped (server-to-server, MOTO-equivalent usage).
 *
 * Expected Omnipay parameters (set by TransactionController):
 *   transactionId  — our DB transaction ID, used as merchantReference
 *   amount         — float, e.g. 10.00 for $10.00 AUD
 *   currency       — ISO 4217, e.g. "AUD"
 *   card           — Omnipay CreditCard object with:
 *                      firstName, lastName, number, expiryMonth, expiryYear, cvv
 */
class PurchaseRequest extends AbstractRequest
{
    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $this->validate('amount', 'currency', 'card');

        $card = $this->getCard();
        $card->validate();

        // WGOP expects amount as integer in smallest currency unit (cents for AUD)
        // Omnipay's getAmountInteger() handles this conversion correctly
        $amountCents = $this->getAmountInteger();

        // WGOP expiry format: MMYY (e.g. "0125" for January 2025)
        // Omnipay stores the year as 4 digits internally, so we take the last 2
        $expiryDate = str_pad((string)$card->getExpiryMonth(), 2, '0', STR_PAD_LEFT)
                    . substr((string)$card->getExpiryYear(), -2);

        return [
            'order' => [
                'amountOfMoney' => [
                    'amount'       => $amountCents,
                    'currencyCode' => strtoupper($this->getCurrency()),
                ],
                'references' => [
                    'merchantReference' => (string)$this->getTransactionId(),
                ],
            ],
            'cardPaymentMethodSpecificInput' => [
                'authorizationMode'  => 'SALE',
                'transactionChannel' => 'MOTO',
                'paymentProductId'   => $this->resolvePaymentProductId($card),
                'card' => [
                    'cardholderName' => trim($card->getFirstName() . ' ' . $card->getLastName()),
                    'cardNumber'     => $card->getNumber(),
                    'expiryDate'     => $expiryDate,
                    'cvv'            => $card->getCvv(),
                ],
                'threeDSecure' => [
                    'skipAuthentication' => true,
                ],
            ],
        ];
    }

    /**
     * Resolve Worldline paymentProductId from Omnipay's built-in getBrand() detection.
     * IDs sourced from GET /v2/{merchantId}/products on the ANZ AU account.
     *
     * UnionPay (56) is not in Omnipay's brand list — it falls through to the
     * Discover regex (which covers 62xx ranges) so we leave it unmapped for now.
     */
    protected function resolvePaymentProductId(\Omnipay\Common\CreditCard $card): int
    {
        $map = [
            \Omnipay\Common\CreditCard::BRAND_VISA        => 1,
            \Omnipay\Common\CreditCard::BRAND_MASTERCARD  => 3,
            \Omnipay\Common\CreditCard::BRAND_AMEX        => 2,
            \Omnipay\Common\CreditCard::BRAND_DINERS_CLUB => 132,
        ];

        $brand = $card->getBrand();

        return $map[$brand] ?? 1; // default Visa
    }

    /**
     * @throws \Exception
     */
    public function sendData(mixed $data): PurchaseResponse
    {
        $merchantId  = $this->getMerchantId();
        $requestUri  = "/v2/{$merchantId}/payments";
        $url         = $this->getBaseUrl() . $requestUri;
        $contentType = 'application/json';
        $date        = gmdate('D, d M Y H:i:s') . ' GMT'; // RFC 7231
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

        return $this->response = new PurchaseResponse($this, $responseData, $statusCode);
    }
}
