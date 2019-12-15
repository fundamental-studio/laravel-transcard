# Laravel Transcard
Laravel wrapper for easy and seamless integration with Transcard VPOS.

Made with love and code by [Fundamental Studio Ltd.](https://www.fundamental.bg)

## Installation

The package is compatible with Laravel 5.8+ version.

Via composer:
``` bash
$ composer require fmtl-studio/laravel-transcard
```

After installing, the package should be auto-discovered by Laravel.
In order to configurate the package, you need to publish the config file using this command:
``` bash
$ php artisan vendor:publish --provider="Fundamental\Transcard\TranscardServiceProvider"
```

After publishing the config file, you should either add the needed keys to the global .env Laravel file:
```

```

You are up & running and ready to go.

## Documentation and Usage instructions

The usage of our package is pretty seamless and easy.
First of all, you need to use the proper namespace for our package:
```
use Fundamental\Transcard\Transcard;
```

Creating the instance of our package:
``` php
$epay = new Transcard('paylogin', array $data, 'BG'); // Use either paylogin or credit_paydirect, the second parameter is documented in the next section and the third parameter is the request language page will be shown in: BG or EN, default: BG.
$epay->setData(
    1000000000, // Could be either number or false(will be auto-generated if EPAY_GENERATE_INVOICE=TRUE)
    40.00, // Amount of the payment, double formatted either as double or string
    '14.12.2019 20:46:00', // Could be either formatted date in d.m.Y H:i:s or false(will be auto-generated)
    'Description of the payment in less than 100 symbols.', // Could be empty
    'BGN', // Available currencies: BGN, USD, EUR, default to bgn, may be ommited
    'utf-8' // Encoding, either null or utf-8, may be ommitted
);
```
The setData function could be ommitted. The data may be set as array and second parameter to the constructor of the main class.
``` php
$epay = new Transcard('paylogin', [
    'invoice' => 1000000000, // Could be either number or false(will be auto-generated if EPAY_GENERATE_INVOICE=TRUE)
    'amount' => 40.00, // Amount of the payment, double formatted either as double or string
    'expiration' => '14.12.2019 20:46:00', // Could be either formatted date in d.m.Y H:i:s or false(will be auto-generated)
    'description' => 'Description of the payment in less than 100 symbols.' // Could be empty
]);
```
All available methods are shown into the next section, including setter and getter methods.

Retrieve the correct and formatted hidden fields, form, or array with all the needed parameters.
``` php
// Both, URL OK and URL Cancel can be ommitted as not required by the ePay platform.

// Would return all hidden fields as formatted html
$epay->generatePaymentFields('https://ok.url', 'https://cancel.url');

// Would return html form with the first parameter as id
$epay->generatePaymentForm('#form-id', 'https://ok.url', 'https://cancel.url');

// Would return array with all needed parameters for the platform request you need to do on your own
$epay->getPaymentParameters();
```
All available methods are shown into the next section.

## Changelog
All changes are available in our Changelog file.

## Support
For any further questions, feature requests, problems, ideas, etc. you can create an issue tracker or drop us a line at support@fundamental.bg

## Contributing
Read the Contribution file for further information.

## Credits

- Konstantin Rachev
- Vanya Ananieva

The package is bundled and contributed to the community by Fundamental Studio Ltd.'s team.

## Issues
If you discover any issues, please use the issue tracker.

## Security
If your discover any security-related issues, please email konstantin@fundamental.bg or support@fundamental.bg instead of using the issue tracker.

## License
The MIT License(MIT). See License file for further information and reading.