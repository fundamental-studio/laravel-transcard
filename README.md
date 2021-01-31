# Laravel Transcard
Laravel wrapper for easy and seamless integration with Transcard VPOS.

Made with love and code by [Fundamental Studio Ltd.](https://www.fundamental.bg)

## Installation

The package is compatible with Laravel 7+ version.

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
TRANSCARD_MERCHANT_ID=XXXXXXXXXX  # Official Merchant ID number, obtained from Transcard
TRANSCARD_PRODUCTION=FALSE # Should the platform use the production or the test Transcard endpoint
TRANSCARD_PRIVATE_KEY="" # Location of your private key file, make sure it is not available to public
TRANSCARD_PRIVATE_KEY_PASS="" # Location of your private key password, make sure it this file is not available to public
TRANSCARD_PUBLIC_KEY="" # Location of your public key file, make sure it is not available to public
TRANSCARD_RETURN_URL=""
TRANSCARD_GENERATE_INVOICE=TRUE # Should the package generate random invoice number if one isn't presented
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
$epay = new Transcard('paylogin', array $data, 'BG'); // The second parameter is documented in the next section and the third parameter is the request language page will be shown in: BG or EN, default: BG.
$epay->setData(
    1000000000, // Could be either number or false(will be auto-generated if EPAY_GENERATE_INVOICE=TRUE)
    40.00, // Amount of the payment, double formatted either as double or string
    'Description of the payment in less than 100 symbols.', // Could be empty
);
```
The setData function could be ommitted. The data may be set as array and second parameter to the constructor of the main class.
``` php
$epay = new Transcard('paylogin', [
    'invoice' => 1000000000, // Could be either number or false(will be auto-generated if EPAY_GENERATE_INVOICE=TRUE)
    'amount' => 40.00, // Amount of the payment, double formatted either as double or string
    'description' => 'Description of the payment in less than 100 symbols.' // Could be empty
]);
```
All available methods are shown into the next section, including setter and getter methods.

Retrieve the correct and formatted hidden fields, form, or array with all the needed parameters.
``` php
// Would return all hidden fields as formatted html
$epay->generatePaymentFields();

// Would return html form with the first parameter as id
$epay->generatePaymentForm('#form-id');

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