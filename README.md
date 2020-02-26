# [PHP] Ultra Small Proxy
### PHP 5.6+ / PHP 7

Current version: 1.1

Ultra Small Proxy is a light-weight proxy written in PHP. 

## Features:

- Proxy server written in PHP
- Easy usage and integration
- Standalone and external usage (it is only one PHP class)
- Simple and light-weight
- Sessions support
- Sending and receiving cookies
- GET and POST connections
- Forms submiting support
- POST variables redirecting
- Toolbar with address bar and connection debugger
- URLs rewriting at runtime (links, images, css, javascript, etc.)
- 2 different methods for URLs rewriting: Regex (with preg_replace) and DOM XML (with libxml / DOMDocument)
- PHP 5 and PHP 7 supported


## Usage example:
```
<?php

/* include class */
include __DIR__.'/UltraSmallProxy.php';  

/* instantiate proxy */
$proxy = new UltraSmallProxy();

/* specify start page and load it! */
$output = $proxy->load('https://github.com'); 

/* render output */
echo $output;

```
Make sure to have write permissions to `./cookies` directory.

## Screenshot:

![proxy_github](https://user-images.githubusercontent.com/61396542/75244037-4c66bb00-57cb-11ea-82ee-c51c7736653b.png)


Open `loopback.php`if you want to test proxy features like sessions support, POST vars redirecting, form submiting and more, e.g.:
```
<?php
$output = $proxy->load('http://localhost/loopback.php'); 
```

## Repository contents:

- `UltraSmallProxy.php` - proxy class
- `index.php` - usage example
- `loopback.php` - loopback for test features like session support, form vars sending, and cookies redirecting


## Options:

```
$proxy = new UltraSmallProxy($init = true, $rewriteMode = 'regex', $attachToolbar = true);
```
`$init` - autostarts listening for URLs in QUERY STRING, set to `false` if you want to disable this feature, default: `true`

`$rewriteMode` - method used to URLs rewriting, possible values: `regex` (rewriting by regular expressions), `dom` (rewriting by DOM XML), `null` (disable rewriting), default: `regex`

`$attachToolbar` - appends toolbar DIV, CSS and JS to output when `true`, set to `false` if you want to disable this feature, default: `true`


```
$output = $proxy->load('https://github.com', $force = false); 
```
`$force` - if set to `false` then URLs given from QUERY STRING are always overwriting URLs passed here, set to `true` if you want to reverse this behaviour, default: `false`


## Public methods:

Before page load:

`$proxy->setUserAgent($agent)` - sets custom User Agent, defaul: `Mozilla/4.0 (compatible;)`

`$proxy->setRewriteMode($rewriteMode)` - sets URLs rewrite mode: `regex`|`dom`|`null`

`$proxy->setMethod($method)` - sets request method: `GET`|`POST`

`$proxy->setCookiesDir($path)` - sets directory for proxied cookies storage, default: `cookies`



After page load:

`$siteCookies = $proxy->getSiteCookies()` - returns cookies[] received from proxied site

`$localCookies = $proxy->getLocalCookies()` - returns cookies[] stored localy and sended to proxied site

`$status = $proxy->getStatus()` - returns connection status[]

`$sid = $proxy->getSid()` - returns proxied PHPSESSID if exists

`$isError = $proxy->isError()` - returns `true` if any error occured, `false` if not

`$isCurlError = $proxy->isCurlError()` - returns `true` if curl error occured, `false` if not

`$errorMessages = $proxy->getErrorMessages()` - returns error messages[] if occured


 
Other:

`$parsed = $proxy->parse($html)` - parse/rewrite URLs in custom html content with selected `$rewriteMode`



 
### Ultra Small Proxy is free to use but if you liked then you can donate project via BTC: 

**1LK9tDPBuBFXCKUThFWXNvdcdJ4gzx1Diz**

or by PayPal:
 **[https://www.paypal.me/szczyglinski](https://www.paypal.me/szczyglinski)**


Enjoy!



MIT License | 2020 Marcin 'szczyglis' Szczygliński

https://github.com/szczyglis-dev/php-ultra-small-proxy

Contact: szczyglis@protonmail.com
