<h6 align="center">
    <img src="https://eatcard.s3.eu-central-1.amazonaws.com/WOT-Logo-2-(1280x640).png" width="250"/>
</h6>
<h2 align="center">
    Eatcard Companion
</h2>
<br>

![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/weboccult/eatcard-companion/run-tests/master?style=for-the-badge)
![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/weboccult/eatcard-companion/run-tests?label=tests&style=for-the-badge)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/weboccult/eatcard-companion/Check%20&%20fix%20styling?label=Check%20%26%20fix%20styling&logo=github&style=for-the-badge)
![GitHub](https://img.shields.io/github/license/weboccult/eatcard-companion?style=for-the-badge)

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/weboccult/eatcard-companion?style=for-the-badge)
![Latest Version on Packagist](https://img.shields.io/packagist/v/weboccult/eatcard-companion?style=for-the-badge)

![GitHub last commit](https://img.shields.io/github/last-commit/weboccult/eatcard-companion?style=for-the-badge)
![GitHub Release Date](https://img.shields.io/github/release-date/weboccult/eatcard-companion?label=Latest%20Release&style=for-the-badge)

![Total Downloads](https://img.shields.io/packagist/dt/weboccult/eatcard-companion.svg?style=for-the-badge)
![GitHub contributors](https://img.shields.io/github/contributors/weboccult/eatcard-companion?style=for-the-badge)

[comment]: <> ([![GitHub Code Style Action Status]&#40;https://img.shields.io/github/workflow/status/weboccult/eatcard-companion/Check%20&%20fix%20styling?label=code%20style&#41;]&#40;https://github.com/weboccult/eatcard-companion/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain&#41;)

---

This companion package will help our team to manage to generic functionality and features at one place.

## Installation

#### You can install the package via composer:

```bash
composer require weboccult/eatcard-companion
```

## Usage


```php
use Weboccult\EatcardCompanion\Traits\TRAIT_NAME;
use function Weboccult\EatcardCompanion\Helpers\{FUNCTION1, FUNCTION2};
```

## Available Services

- Order : To handle common order creation logic
- Print : To generate common json for all type of prints - SQS | Protocol | Label | Full Receipt | Performa Receipt 
  | Kitchen print.
- Sms : to send sms with very fluent and elegant API
- MultiSafe : to handle mutisafe transactions
- OneSignal : to handle push notification for mobile apps

Please check below documents for more details 
- [ORDER_SERVICE_USAGE.md](Docs/ORDER_SERVICE_USAGE.md)
- [PRINT_SERVICE_USAGE.md](Docs/PRINT_SERVICE_USAGE.md)  
- [EATCARD_SMS_USAGE.md](Docs/EATCARD_SMS_USAGE.md)

## Companion Config

here's how you can use publish companion config file to manage numerous settings

```php
php artisan eatcardcompanion:publish --type=config
```
It will publish eatcardCompanion.php to your parent project

Please check [EATCARD_COMPANION.md](Docs/EATCARD_COMPANION.md) for more details

## Sms Config & Migration

here's how you can use publish sms config file to manage numerous settings

```php
php artisan eatcardsms:publish --type=config
```
It will publish eatcardSms.php to your parent project

```php
php artisan eatcardsms:publish --type=migration
```
It will publish one migration to your parent project to store sms history.

Please check [EATCARD_SMS.md](Docs/EATCARD_SMS.md) for more details



## Translation Support

Please check [TRANSLATION_USAGE.md](Docs/TRANSLATION_USAGE.md) for more details

## View Support

Please check [VIEW_USAGE.md](Docs/VIEW_USAGE.md) for more details

## Traits

- Please check [TRAITS.md](Docs/TRAITS.md) for more details

## Helper Functions

- Please check [HELPERS.md](Docs/HELPERS.md) for more details


## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

- [Darshit Hedpara](https://github.com/darshithedpara)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
