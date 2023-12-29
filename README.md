# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shengamo/tumeny-pay.svg?style=flat-square)](https://packagist.org/packages/shengamo/tumeny-pay)
[![Total Downloads](https://img.shields.io/packagist/dt/shengamo/tumeny-pay.svg?style=flat-square)](https://packagist.org/packages/shengamo/tumeny-pay)
![GitHub Actions](https://github.com/shengamo/tumeny-pay/actions/workflows/main.yml/badge.svg)

# TumenyPay Laravel Package

This Laravel package integrates Tumeny Pay as a payment gateway into your Laravel application.

## Installation

1. Install the package via Composer:

    ```bash
    composer require shengamo/tumeny-pay
    ```

2. Publish the package migrations:

    ```bash
    php artisan vendor:publish --tag=tumeny-pay-migrations
    ```

3. Add the following environment variables to your `.env` file:

    ```env
    TUMENY_KEY=your_tumeny_key
    TUMENY_SECRET=your_tumeny_secret
    TUMENY_BASE_URL=https://tumeny.herokuapp.com/api/
    ```

## Usage

### Payment Request

To initiate a payment request, you can use the `processPayment` method provided by the `TumenyPay` class.

```php
use Shengamo\TumenyPay\TumenyPay;

$tumeny = new TumenyPay();
$tumeny->processPayment('amount', 'plan name or default', 'Zambian mobile number', 'quantity of items', 'description');

// e.g.
$tumeny->processPayment(100, 'default', '0961234567', 1, 'custom description');
```
### Setup the verification of payments
To automatically verify pending order payments, add the following line to the schedule function in your App\Console\Kernel.php file:

```php
use Shengamo\TumenyPay\Jobs\VerifyPendingOrderPayments;

$schedule->job(new VerifyPendingOrderPayments())->everyMinute();

```

You can now access the completed payment in the ShengamoOrder table in your DB. If you have a subscription app, you could add an observer for the ShengamoOrder Model and activate your subscription if ShengamoOrder has a status of 2 (Success).
### Testing

```bash
./vendor/bin/phpunit
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mo@shengamo.com instead of using the issue tracker.

## Credits

-   [Mo Malenga](https://github.com/shengamo)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

