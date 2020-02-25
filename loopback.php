<?php

session_start();

echo '<h1>ULTRA SMALL PROXY - LOOPBACK TEST</h1><hr>';
echo '<h1>URLS REWRITE TEST</h1>';
echo '<h5>(check the page source here)</h5>';
echo '<link rel="stylesheet" type="text/css" href="some_stylesheet.css"><br/>';
echo '<link rel="stylesheet" type="text/css" href="some_file_not_css"><br/>';
echo '<script src="some_js_script"></script><br/>';
echo '<img src="some_image_should_be_prefixed.png"><br/>';
echo '<a href="some_file.html">TEST LINK</a><br/><hr>';

echo '<h1>SESSION TEST | CURRENT SID: ' . session_id() . '</h1><hr>';

echo '<h1>REQUEST METHOD: ' . $_SERVER['REQUEST_METHOD'] . '</h1><hr>';

function dump($var, $title = null)
{
    if (!is_null($title)) echo '<h2>' . $title . '</h2>';
    echo '<div class="vars"><pre>';
    var_dump($var);
    echo '</pre></div>';
}

setcookie('test_cookie1', 'cookie1_value');
setcookie('test_cookie2', 'cookie2_value');
setcookie('test_cookie3', 'cookie3_value');

echo '<h1>VARS DEBUG</h1>';
dump($_COOKIE, '$_COOKIE');
dump($_GET, '$_GET');
dump($_POST, '$_POST');

echo '<hr><h1>FORM SUBMIT TEST</h1>';
echo '<form method="POST" action="">
<input type="text" name="form_test" value="test" />
<input type="submit" value="SUBMIT" />
</form>';