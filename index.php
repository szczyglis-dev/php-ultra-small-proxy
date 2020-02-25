<?php

include __DIR__.'/UltraSmallProxy.php';

$proxy = new UltraSmallProxy();

$output = $proxy->load('https://github.com'); // <-- specify start page here

echo $output;