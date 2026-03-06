# Psr16Storage

[![CI](https://github.com/HonkLegion/Psr16Storage/actions/workflows/ci.yml/badge.svg)](https://github.com/HonkLegion/Psr16Storage/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/HonkLegion/Psr16Storage/badge.svg?branch=main)](https://coveralls.io/github/HonkLegion/Psr16Storage?branch=main)
[![Latest Stable Version](https://poser.pugx.org/honklegion/psr16storage/v/stable)](https://packagist.org/packages/honklegion/psr16storage)
[![Total Downloads](https://poser.pugx.org/honklegion/psr16storage/downloads)](https://packagist.org/packages/honklegion/psr16storage)
[![License](https://poser.pugx.org/honklegion/psr16storage/license)](https://packagist.org/packages/honklegion/psr16storage)

Nette cache `Storage` adapter backed by a PSR-16 cache implementation.

## Installation

```bash
composer require honklegion/psr16storage
```

## Requirements

- PHP `>=8.2 <8.6`
- `nette/caching` `^3.2`
- `psr/simple-cache` `^1.0|^3.0`

## Usage

Use any PSR-16 cache and wrap it with `Psr16Storage`.

```php
<?php

declare(strict_types=1);

use HonkLegion\Psr16Storage\Psr16Storage;
use Nette\Caching\Cache;

/** @var \Psr\SimpleCache\CacheInterface $psr16 */
$storage = new Psr16Storage($psr16);
$cache = new Cache($storage);

$cache->save('key', 'value', [
    Cache::Expire => time() + 300,
]);

$value = $cache->load('key');
```

Optional max TTL forwarded to PSR-16 backend:

```php
$storage = new Psr16Storage($psr16, 3600); // max 1 hour
```

## Notes and Limitations

- `Cache::Items` dependency is not supported and throws `Nette\NotSupportedException`.
- `Cache::Tags` and `Cache::Priority` are not supported and throw `Nette\InvalidStateException`.
- Callback dependencies are supported and invalid entries are removed.
- Sliding expiration is supported.

## Development

```bash
composer test
composer phpcs
composer phpstan
```

CI runs on PHP `8.2`, `8.3`, `8.4`, and `8.5`.
