# Omnipay: ANZ Worldline WGOP

**Omnipay driver for the ANZ Worldline Global Online Pay (WGOP) payment gateway.**

This package provides a server-to-server (MOTO) payment integration for the ANZ Worldline WGOP API, built on top of the [Omnipay](https://omnipay.thephpleague.com/) payment processing library.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net)

---

## Requirements

- PHP 8.1+
- [omnipay/common](https://github.com/thephpleague/omnipay-common) ~3.0
- An ANZ Worldline WGOP merchant account

## Installation

```bash
composer require aimalazmi/omnipay-wgop
```

## Configuration

You will need three credentials from your ANZ Worldline merchant portal:

| Parameter      | Description                                    |
|----------------|------------------------------------------------|
| `merchantId`   | PSP / Merchant ID (used in the API URL path)   |
| `apiKeyId`     | API Key ID (used in the Authorization header)  |
| `apiSecretKey` | API Secret Key (used to sign requests)         |

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('WGOP');

$gateway->setMerchantId('your-merchant-id');
$gateway->setApiKeyId('your-api-key-id');
$gateway->setApiSecretKey('your-api-secret-key');
$gateway->setTestMode(true); // set to false for production
```

**Environment variable overrides** (optional):

```
WGOP_BASE_URL=https://payment.anzworldline-solutions.com.au
WGOP_TEST_BASE_URL=https://payment.preprod.anzworldline-solutions.com.au
```

---

## Usage

### Purchase (Charge)

```php
$response = $gateway->purchase([
    'transactionId' => 'your-unique-order-id',
    'amount'        => '49.95',
    'currency'      => 'AUD',
    'card'          => [
        'firstName'   => 'John',
        'lastName'    => 'Smith',
        'number'      => '4111111111111111',
        'expiryMonth' => '12',
        'expiryYear'  => '2027',
        'cvv'         => '123',
    ],
])->send();

if ($response->isSuccessful()) {
    // Store $response->getTransactionReference() — needed for refunds
    $wgopPaymentId = $response->getTransactionReference();
    $status        = $response->getPaymentStatus(); // e.g. "CAPTURED"
} else {
    $error = $response->getMessage();
}
```

### Refund

```php
$response = $gateway->refund([
    'transactionReference' => 'wgop-payment-id-from-purchase', // from getTransactionReference()
    'amount'               => '49.95', // partial refunds supported
    'currency'             => 'AUD',
])->send();

if ($response->isSuccessful()) {
    // Refund accepted
} else {
    $error = $response->getMessage();
}
```

---

## Supported Card Types

Payment product IDs are resolved automatically from the card number using Omnipay's built-in brand detection:

| Card Brand   | WGOP Product ID |
|--------------|-----------------|
| Visa         | 1               |
| Mastercard   | 3               |
| Amex         | 2               |
| Diners Club  | 132             |

---

## API Details

- **Authorization**: GCS `v1HMAC` signed headers (HMAC-SHA256)
- **Transaction channel**: `MOTO` (server-to-server, 3DS skipped)
- **Authorization mode**: `SALE` (capture immediately)
- **Amount format**: Integer cents (e.g. `4995` for $49.95 AUD)
- **Expiry format**: `MMYY`

### Endpoints

| Environment | Base URL                                            |
|-------------|-----------------------------------------------------|
| Production  | `https://payment.anzworldline-solutions.com.au`     |
| Pre-prod    | `https://payment.preprod.anzworldline-solutions.com.au` |

---

## License

MIT — see [LICENSE](LICENSE).

---

## Author

**Aimal Azmi** — [github.com/aimalazmi](https://github.com/aimalazmi)

Built for [Smart Web Agency](https://smartwebagency.co.uk)