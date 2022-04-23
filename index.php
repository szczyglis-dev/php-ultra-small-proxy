<?php

require __DIR__ . '/vendor/autoload.php';

use Szczyglis\UltraSmallProxy\UltraSmallProxy;
use Szczyglis\UltraSmallProxy\Config;

$config = new Config();

$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com'); // <-- specify start page here