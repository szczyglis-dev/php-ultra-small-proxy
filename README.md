# [PHP] Ultra Small Proxy 2
PHP: 7.2.5+, current release: **2.1** build 2022-04-23

**Ultra Small Proxy is a light-weight proxy written in PHP.**

**Install with composer:**
```
composer require szczyglis/php-ultra-small-proxy
``` 
## Features:
- Proxy server written in PHP
- Easy usage and integration
- Simple and light-weight
- Sessions support
- Sending and receiving cookies
- Sending and receiving HTTP headers
- Cache and assets storage
- Domain and IP/host connection support
- HTTP Basic Auth support
- GET and POST connections
- Forms submiting support
- POST variables redirecting
- Toolbar with address bar, configuration and debugger
- URLs rewriting/proxying at runtime (links, images, css, javascript, etc.)
- 2 different methods for URLs rewriting: Regex (with preg_replace) and XML (with libxml/DOM)
- PHP 7.2.5+ supported

## Requirements:

- PHP 7.2.5+ with CURL and XML extensions
- Composer - https://getcomposer.org/


## Usage example:
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();

$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com'); // <-- specify start page here

```
Make sure to have write permissions to `./cookies` and `./cache` directories.

### BE CAREFUL: directory with cookies should not be available to the public!

## Screenshot:

![proxy](https://user-images.githubusercontent.com/61396542/155353063-fde84995-6e43-46c4-8a1c-b8b4772e6dfc.png)


Open `loopback.php` if you want to test proxy features like sessions support, POST vars redirecting, form submiting and more, e.g.:
```php
<?php

$output = $proxy->load('http://localhost/loopback.php'); 
```

## Repository contents:

- `src/` - proxy classes

- `index.php` - usage example

- `loopback.php` - loopback for test features like session support, form vars sending, and cookies redirecting


## Basic usage:

```php
use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();
$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com');
```

## Configuration:

```php
$output = $proxy->load('https://github.com', $force = false); 
```
boolean `$force` - if set to `false` then URLs given from QUERY STRING are always overwriting URLs passed here, set to `true` if you want to reverse this behaviour, default: `false`


## Config values:

**Before page load:**

**$config->set('init', true)** - `boolean`, auto-init `true|false`, default: `true`

**$config->set('source', 'domain')** - `string`, input source `domain|ip`, default: `domain`

**$config->set('raw', false)** - `boolean`, raw download `true|false`, default: `false`

**$config->set('toolbar', true)** - `boolean`, attach toolbar  `true|false`, default: `true`

**$config->set('user_agent', 'Mozilla/4.0 (compatible;)')** - `string`, user agent, default: `Mozilla/4.0 (compatible;)`

**$config->set('timeout', 120)** - `int`, curl timeout, default: `120`

**$config->set('max_redirects', 10)** - `int`, curl max redirects, default: `10`

**$config->set('cookies_dir', './cookies')** - `string`, cookies directory, default: `./cookies`

**$config->set('cache_dir', './cache')** - `string`, cache directory, default: `./cache`

**$config->set('method', 'GET')** - `string`, request method `GET|POST`

**$config->set('rewrite', 'REGEX2')** - `string`, rewrite method `REGEX,REGEX2,REGEX3,DOM`, default: `REGEX2`

**$config->set('rewrite_url', true)** - `boolean`, enable URL rewriting `true|false`, default: `true`

**$config->set('rewrite_img', true)** - `boolean`, enable IMG rewriting `true|false`, default: `true`

**$config->set('rewrite_js', true)** - `boolean`, enable JS rewriting `true|false`, default: `true`

**$config->set('rewrite_form', true)** - `boolean`, enable FORM ACTION rewriting `true|false`, default: `true`

**$config->set('rewrite_css', true)** - `boolean`, enable CSS rewriting `true|false`, default: `true`

**$config->set('rewrite_video', true)** - `boolean`, enable VIDEO rewriting `true|false`, default: `true`

**$config->set('rewrite_ip', true)** - `boolean`, enable domain to IP+Host resolve `true|false`, default: `true`

**$config->set('assets', 'REDIRECT')** - `string`, assets proxying mode `REDIRECT|CURL`, default: `REDIRECT`

**$config->set('is_cfg', false)** - `boolean`, show options `true|false`, default: `false`

**$config->set('is_dbg', false)** - `boolean`, show debug `true|false`, default: `false`

**$config->set('htaccess_user', 'user')** - `string`, HTTP AUTH user

**$config->set('htaccess_pass', 'pass')** - `string`, HTTP AUTH password


## Public methods:

**After page load:**

**$siteCookies = $proxy->cookie->getSiteCookies()** - returns cookies[] received from proxied site

**$localCookies = $proxy->cookie->getLocalCookies()** - returns cookies[] stored localy and sended to proxied site

**$status = $proxy->http->getStatus()** - returns connection status[]

**$headers = $proxy->http->getHeaders()** - returns received headers[]

**$sid = $proxy->getSid()** - returns proxied PHPSESSID if exists

**$errors = $proxy->getErrors()** - returns error messages[] if occured

 
**Others:**

$parsed = $proxy->render($html) - parse/rewrite URLs in custom html content with selected `$rewriteMode`

---

### Changelog 

- `2.1` -- package was added to packagist (2022-04-23)
 
### Ultra Small Proxy is free to use but if you liked then you can donate project via BTC: 

**14X6zSCbkU5wojcXZMgT9a4EnJNcieTrcr**

or by PayPal:
 **[https://www.paypal.me/szczyglinski](https://www.paypal.me/szczyglinski)**


**Enjoy!**

MIT License | 2022 Marcin 'szczyglis' Szczygliński

https://github.com/szczyglis-dev/php-ultra-small-proxy

https://szczyglis.dev

Contact: szczyglis@protonmail.com
