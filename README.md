# [PHP] Ultra Small Proxy 2
### PHP: 7.0+

Current version: **2.0** | 2022-02-23

Ultra Small Proxy is a light-weight proxy written in PHP. 

## Key Features:

- Proxy server written in PHP
- Easy usage and integration
- Standalone and external usage (it is only one PHP file)
- Simple and light-weight
- Sessions support
- Sending and receiving cookies
- Sending and receiving HTTP headers **(NEW in 2.0+)**
- Cache and assets storage **(NEW in 2.0+)**
- Domain and IP/host connection support **(NEW in 2.0+)**
- HTTP Basic Auth support **(NEW in 2.0+)**
- GET and POST connections
- Forms submiting support
- POST variables redirecting
- Toolbar with address bar, configuration and debugger
- URLs rewriting/proxying at runtime (links, images, css, javascript, etc.)
- 2 different methods for URLs rewriting: Regex (with preg_replace) and DOM XML (with libxml / DOMDocument)
- PHP 7.0+ supported

## Requirements:

- PHP 7.0+
- CURL extension
- DOM XML extension

## Usage example:
```
<?php

/* include classes */
include __DIR__.'/UltraSmallProxy.php';  

/* instantiate config */
$config = new UltraSmallProxyConfig();

/* instantiate proxy with config */
$proxy = new UltraSmallProxy($config);

/* specify start page and load it! */
$output = $proxy->load('https://github.com'); 

/* render output */
echo $output;

```
Make sure to have write permissions to `./cookies` directory.
Make sure to have write permissions to `./cache` directory.

### BE CAREFUL: directory with cookies should not be available to the public!

## Screenshot:

![proxy](https://user-images.githubusercontent.com/61396542/155353063-fde84995-6e43-46c4-8a1c-b8b4772e6dfc.png)


Open `loopback.php`if you want to test proxy features like sessions support, POST vars redirecting, form submiting and more, e.g.:
```
<?php
$output = $proxy->load('http://localhost/loopback.php'); 
```

## Repository contents:

- `UltraSmallProxy.php` - proxy class
- `index.php` - usage example
- `loopback.php` - loopback for test features like session support, form vars sending, and cookies redirecting


## Basic usage:

```
$config = new UltraSmallProxyConfig();
$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com');
```

## Configuration:

```
$output = $proxy->load('https://github.com', $force = false); 
```
`$force` - if set to `false` then URLs given from QUERY STRING are always overwriting URLs passed here, set to `true` if you want to reverse this behaviour, default: `false`


## Config values:

Before page load:

`$config->set('init', bool);`- auto-init `true|false`, default: `true`

`$config->set('source', 'string');` - input source `domain|ip`, default: `domain`

`$config->set('raw', bool);` - raw download `true|false`, default: `false`

`$config->set('toolbar', bool);` - attach toolbar  `true|false`, default: `true`

`$config->set('user_agent', 'string');` - user agent, default: `Mozilla/4.0 (compatible;)`

`$config->set('timeout', int);` - curl timeout, default: `120`

`$config->set('max_redirects', int);` - curl max redirects, default: `10`

`$config->set('cookies_dir', 'string');` - cookies directory, default: `./cookies`

`$config->set('cache_dir', 'string');` - cache directory, default: `./cache`

`$config->set('method', 'string');` - request method `GET|POST`

`$config->set('rewrite', 'string');` - rewrite method `REGEX,REGEX2,REGEX3,DOM`, default: `REGEX2`

`$config->set('rewrite_url', bool);` - enable URL rewriting `true|false`, default: `true`

`$config->set('rewrite_img', bool);` - enable IMG rewriting `true|false`, default: `true`

`$config->set('rewrite_js', bool);` - enable JS rewriting `true|false`, default: `true`

`$config->set('rewrite_form', bool);` - enable FORM ACTION rewriting `true|false`, default: `true`

`$config->set('rewrite_css', bool);` - enable CSS rewriting `true|false`, default: `true`

`$config->set('rewrite_video', bool);` - enable VIDEO rewriting `true|false`, default: `true`

`$config->set('rewrite_ip', bool);` - enable domain to IP+Host resolve `true|false`, default: `true`

`$config->set('assets', 'string');` - assets proxying mode `REDIRECT|CURL`, default: `REDIRECT`

`$config->set('is_cfg', bool);` - show options `true|false`, default: `false`

`$config->set('is_dbg', bool);` - show debug `true|false`, default: `false`

`$config->set('htaccess_user', 'string');` - HTTP AUTH user

`$config->set('htaccess_pass', 'string');` - HTTP AUTH password


## Public methods:

After page load:

`$siteCookies = $proxy->cookie->getSiteCookies()` - returns cookies[] received from proxied site

`$localCookies = $proxy->cookie->getLocalCookies()` - returns cookies[] stored localy and sended to proxied site

`$status = $proxy->http->getStatus()` - returns connection status[]

`$headers = $proxy->http->getHeaders()` - returns received headers[]

`$sid = $proxy->getSid()` - returns proxied PHPSESSID if exists

`$errors = $proxy->getErrors()` - returns error messages[] if occured

 
Other:

`$parsed = $proxy->render($html)` - parse/rewrite URLs in custom html content with selected `$rewriteMode`

---
 
### Ultra Small Proxy is free to use but if you liked then you can donate project via BTC: 

**1LK9tDPBuBFXCKUThFWXNvdcdJ4gzx1Diz**

or by PayPal:
 **[https://www.paypal.me/szczyglinski](https://www.paypal.me/szczyglinski)**


Enjoy!

MIT License | 2022 Marcin 'szczyglis' Szczygliński

https://github.com/szczyglis-dev/php-ultra-small-proxy

Contact: szczyglis@protonmail.com
