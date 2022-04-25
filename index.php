<?php

/**
 * This file is part of szczyglis/php-ultra-small-proxy.
 *
 * (c) Marcin Szczyglinski <szczyglis@protonmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * @package szczyglis/php-ultra-small-proxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @copyright 2022 Marcin Szczyglinski
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 */

use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();

$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com'); // <-- specify start page here