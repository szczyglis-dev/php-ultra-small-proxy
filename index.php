<?php

include __DIR__.'/UltraSmallProxy.php';

$config = new UltraSmallProxyConfig();
$proxy = new UltraSmallProxy($config);

echo $proxy->load('https://github.com'); // <-- specify start page here