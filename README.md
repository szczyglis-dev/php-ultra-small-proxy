Release: **2.1.3** | build: **2024.08.26** | PHP: **^7.2.5|^8.0**

# Ultra Small Proxy 2

**Ultra Small Proxy is a lightweight proxy written in PHP.**

## How to install
```
composer require szczyglis/php-ultra-small-proxy
``` 
## Features
- Proxy server written in PHP
- Easy to use and integrate
- Simple and lightweight
- Sessions support
- Supports sending and receiving cookies
- Supports sending and receiving HTTP headers
- Cache and asset storage
- Domain and IP/host connection support
- HTTP Basic Auth support
- GET and POST request handling
- Form submission support
- POST variable redirection
- Toolbar with address bar, configuration, and debugger
- URLs rewritten/proxied at runtime (links, images, CSS, JavaScript, etc.)
- Two different methods for URL rewriting: Regex (with preg_replace) and XML (with libxml/DOM)
- Supports PHP 7.2.5+ and 8.0+

## Requirements

- PHP 7.2.5+ or 8.0+ with CURL and XML extensions
- Composer - https://getcomposer.org/


## Usage example
```php
<?php
// app.php

require __DIR__ . '/vendor/autoload.php';

use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();

$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com'); // <-- specify start page here

```
Ensure you have write permissions to the `./cookies` and `./cache` directories.

### WARNING: The cookies directory should not be publicly accessible!

## Screenshot

![proxy](https://user-images.githubusercontent.com/61396542/155353063-fde84995-6e43-46c4-8a1c-b8b4772e6dfc.png)


Open `loopback.php` if you want to test proxy features such as session support, POST variable redirection, form submission, and more, e.g.:
```php
<?php

$output = $proxy->load('http://localhost/loopback.php'); 
```

## Repository contents:

- `src/` - proxy classes

- `index.php` - usage example

- `loopback.php` - loopback for testing features like session support, form variable sending, and cookie redirection


## Basic usage

```php

use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();
$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com');
```

## Configuration

```php
$output = $proxy->load('https://github.com', $force = false); 
```
Boolean `$force` - if set to `false`, URLs given in the QUERY STRING will always overwrite URLs passed here. Set to `true` to reverse this behavior. Default: `false`.


## Config values:

Before page load:

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


## Public methods

After page load:

**$siteCookies = $proxy->cookie->getSiteCookies()** - returns cookies[] received from proxied site

**$localCookies = $proxy->cookie->getLocalCookies()** - returns cookies[] stored localy and sended to proxied site

**$status = $proxy->http->getStatus()** - returns connection status[]

**$headers = $proxy->http->getHeaders()** - returns received headers[]

**$sid = $proxy->getSid()** - returns proxied PHPSESSID if exists

**$errors = $proxy->getErrors()** - returns error messages[] if occured

 
Other methods:

**$parsed = $proxy->render($html)** - parses/rewrites URLs in custom HTML content with the selected `$rewriteMode`.

---

### Changelog 

`2.1.0` -- Package was added to Packagist (2022-04-23)

`2.1.1` -- Updated PHPDoc (2022-04-25)

`2.1.2` -- Updated composer.json (2022-04-28)

`2.1.3` -- improved documentation (2024-08-26)

--- 
**Ultra Small Proxy is free to use, but if you like it, you can support my work by buying me a coffee ;)**

https://www.buymeacoffee.com/szczyglis

**Enjoy!**

MIT License | 2022 Marcin 'szczyglis' Szczygliński

https://github.com/szczyglis-dev/php-ultra-small-proxy

https://szczyglis.dev

Contact: szczyglis@protonmail.com
