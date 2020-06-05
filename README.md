# Laravel File Manager


[![Build Status](https://travis-ci.org/srustamov/laravel-file-manager.svg?branch=master)](https://travis-ci.org/srustamov/laravel-file-manager)

[![Latest Stable Version](https://poser.pugx.org/srustamov/laravel-file-manager/v/stable)](https://packagist.org/packages/srustamov/laravel-file-manager)
[![Latest Unstable Version](https://poser.pugx.org/srustamov/laravel-file-manager/v/unstable)](https://packagist.org/packages/srustamov/laravel-file-manager)
[![GitHub license](https://img.shields.io/github/license/srustamov/laravel-file-manager.svg)](https://github.com/srustamov/laravel-file-manager/blob/master/LICENSE.md)

## Preview
![Design](https://i.ibb.co/Jc6kxYk/ezgif-com-video-to-gif.gif|width:100)

## Requirements

- Laravel **^6.0** or **^7.0**
- PHP **7.2**

## Installation

You can install the package via composer:

```bash
composer require srustamov/laravel-file-manager
```

```bash
php artisan vendor:publish --provider="Srustamov\FileManager\FileManagerServiceProvider" --tag="config"
```
```bash
php artisan vendor:publish --provider="Srustamov\FileManager\FileManagerServiceProvider" --tag="public" --force
```





### Testing

``` bash
composer test
```


### Security

If you discover any security related issues, please email rustemovv96@gmail.com instead of using the issue tracker.

## Credits

- [Samir Rustamov](https://github.com/srustamov)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
