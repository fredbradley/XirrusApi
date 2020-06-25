# Xirrus / Cambium Networks XMS API for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fredbradley/xirrusapi.svg?style=flat-square)](https://packagist.org/packages/fredbradley/xirrusapi)
[![Build Status](https://img.shields.io/travis/fredbradley/xirrusapi/master.svg?style=flat-square)](https://travis-ci.org/fredbradley/xirrusapi)
[![Total Downloads](https://img.shields.io/packagist/dt/fredbradley/xirrusapi.svg?style=flat-square)](https://packagist.org/packages/fredbradley/xirrusapi)

A PHP Wrapper for the [XMS](https://www.cambiumnetworks.com/products/software/xms-enterprise/) API from Xirrus / [Cambium Networks](https://www.cambiumnetworks.com/).   

## Installation

You can install the package via composer:

```bash
composer require fredbradley/xirrusapi
```

## Usage

``` php
// Override default options
$default_options = [
   'verify' => true, // set to false, if you are up against SSL verification issues 
   /**
    * Please note: Setting this to false is not recommended 
    * and weakens the security of your system, but
    * sometimes for testing purposes is nessecary
    */
];
$api = new \FredBradley\XirrusApi\XirrusApi("https://xmsserver.tld:9443", "exampleusername", "examplepassword", $default_options);
$api->request("GET", "stations.json"); // This will get you a php json object of your api result dataset
```
Futher helper methods will be coded into this package in time.

## API Documentation
Documentation can be found locally on your own XMS Hosted appliance, under "Settings -> XMS API -> API Documentation"

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email code@fredbradley.co.uk instead of using the issue tracker.

## Credits

- [Fred Bradley](https://github.com/fredbradley)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
