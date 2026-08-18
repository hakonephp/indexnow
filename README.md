# IndexNow Client for PHP 🏃‍♀️

[![Package version](https://img.shields.io/packagist/v/hakone/indexnow.svg?style=flat)](https://packagist.org/packages/hakone/indexnow)
[![Build Status](https://github.com/hakonephp/indexnow/actions/workflows/test.yml/badge.svg?branch=master)](https://github.com/hakonephp/indexnow/actions)
[![Downloads this Month](https://img.shields.io/packagist/dm/hakone/indexnow.svg)](https://packagist.org/packages/hakone/indexnow)

**hakone/indexnow** is an [IndexNow] client implementation based on [PSR-17] and [HTTPlug Discovery].

The HTTP client is not hardcoded. Pass any [PSR-18] client, or let HTTPlug Discovery pick one from the project.

## Install

```
composer require hakone/indexnow
```

## Usage

```php
use Hakone\IndexNow\IndexNow;
use Http\Discovery\Psr18ClientDiscovery;

$client = new IndexNow(Psr18ClientDiscovery::find());

$key = '...';

// Submitting One URL
$client->submitUrl('www.example.com', $key, 'http://www.example.com/product.html');

// Submitting set of URLs
$client->submitList('www.example.com', $key, [
    'https://www.example.com/url1',
    'https://www.example.com/folder/url2',
    'https://www.example.com/url3',
]);
```

## Copyright

```
Copyright 2023 USAMI Kenta <tadsan@zonu.me>

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```

[PSR-17]: https://www.php-fig.org/psr/psr-17/
[PSR-18]: https://www.php-fig.org/psr/psr-18/
[IndexNow]: https://www.indexnow.org/
[HTTPlug Discovery]: https://github.com/php-http/discovery
