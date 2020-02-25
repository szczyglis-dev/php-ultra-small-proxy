<?php

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 1.1 | 2020.02.24
 */
class UltraSmallProxy
{
    /** @var string */
    const GITHUB_URL = 'https://github.com/szczyglis-dev/php-ultra-small-proxy';

    /** @var string */
    const VERSION = '1.1';

    /** @var string */
    const TOOLBAR_SEPARATOR = '<br/><br/>';

    /** @var string */
    public $url;

    /** @var string */
    public $userAgent = 'Mozilla/4.0 (compatible;)';

    /** @var string */
    public $rewriteMode = 'dom'; /* regex | dom */

    /** @var array */
    public $status;

    /** @var int */
    public $error;

    /** @var int */
    public $timeout = 120;

    /** @var int */
    public $maxRedirections = 10;

    /** @var array */
    public $errorMessages = [];

    /** @var string */
    public $response;

    /** @var string */
    public $cookiesDir;

    /** @var string */
    public $method;

    /** @var string */
    public $output;

    /** @var bool */
    private $attachToolbar = true;

    /** @var bool */
    private $isError = false;

    /** @var string */
    private $domain = '';

    /** @var string */
    private $cookieName = '';

    /** @var array */
    private $headers = [];

    /** @var array */
    private $siteCookies = [];

    /** @var array */
    private $localCookies = [];

    /** @var float */
    private $timer = 0;

    /**
     * UltraSmallProxy Constructor.
     * @param bool $init Takes URLs from QUERY STRING if true
     * @param string $rewriteMode Sets rewrite mode, regex|dom|null
     * @param bool $attachToolbar Appends toolbar to output if true
     */
    public function __construct($init = true, $rewriteMode = 'regex', $attachToolbar = true)
    {
        $this->timer = microtime(true);
        $this->rewriteMode = $rewriteMode;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->attachToolbar = $attachToolbar;
        if (empty($this->cookiesDir)) {
            $this->cookiesDir = __DIR__ . '/cookies';
        }

        if (!is_dir($this->cookiesDir)) {
            if (mkdir($this->cookiesDir) === false) {
                $this->isError = true;
                $this->errorMessages[] = 'Error creating ' . $this->cookiesDir . ' directory';
            }
        }

        if ($init) {
            $this->init();
        }
    }

    /**
     * @param string $url Page URL to load
     * @param bool $force If true always overwrites URL given from GET
     * @return string|null
     */
    public function load($url = '', $force = false)
    {
        if (empty($this->url) || $force) {
            $this->url = $url;
        }

        if (!empty($this->url)) {
            $this->domain = parse_url($this->url, PHP_URL_HOST);
            $this->cookieName = session_id() . '_' . $this->domain;
            $this->localCookies = $this->loadCookies();
            $this->response = $this->connect();

            if (isset($this->headers['set-cookie'])) {
                $this->siteCookies = $this->saveCookies($this->headers['set-cookie']);
            }
            $this->localCookies = $this->loadCookies();
        }
        return $this->parseResponse($this->response);
    }

    /**
     * @return null
     */
    private function init()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_GET['u'])) {
            $this->url = urldecode($_GET['u']);
        } else {
            $this->url = urldecode($_SERVER['QUERY_STRING']);
        }
        if (isset($_GET['r'])) {
            $this->rewriteMode = $_GET['r'];
        }
        if (isset($_GET['m'])) {
            $this->method = $_GET['m'];
            if ($_GET['m'] == 'GET' && $_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->method = 'POST';
            }
        }
    }

    /**
     * @param array $arr
     * @return int|string|null
     */
    private function arrayKeyFirst($arr)
    {
        foreach ($arr as $key => $tmp) {
            return $key;
        }
        return null;
    }

    /**
     * @return array|null
     */
    private function loadCookies()
    {
        if (file_exists(rtrim($this->cookiesDir, '/') . '/' . $this->cookieName)) {
            $data = file_get_contents(rtrim($this->cookiesDir, '/') . '/' . $this->cookieName);
            if (!empty($data)) {
                return unserialize($data);
            }
        }
    }

    /**
     * @param $cookie
     * @return array
     */
    private function parseCookie($cookie)
    {
        $data = [];
        $e = explode('; ', $cookie);
        foreach ($e as $part) {
            $c = explode('=', $part);
            if (isset($c[1])) {
                $data[$c[0]] = $c[1];
            }
        }
        return $data;
    }

    /**
     * @param $cookies
     * @return array
     */
    private function parseCookies($cookies)
    {
        $data = [];
        foreach ($cookies as $cookie) {
            if (!empty($cookie)) {
                $parsed = $this->parseCookie($cookie);
                $key = $this->arrayKeyFirst($parsed);
                $data[$key] = $parsed;
            }
        }
        return $data;
    }

    /**
     * @param $cookies
     * @return array
     */
    private function saveCookies($cookies)
    {
        $cookies_ary = $this->parseCookies($cookies);
        foreach ($cookies_ary as $key => $cookie) {
            $this->localCookies[$key] = $cookie;
        }
        $serialized = serialize($this->localCookies);
        $path = rtrim($this->cookiesDir, '/') . '/' . $this->cookieName;
        if (file_put_contents($path, $serialized) === false) {
            $this->isError = true;
            $this->errorMessages[] = 'Error saving cookies file: ' . $path;
        }
        return $cookies_ary;
    }

    /**
     * @param $cookies
     * @return string
     */
    private function showCookies($cookies)
    {
        $html = [];
        foreach ($cookies as $key => $cookie) {
            if (is_array($cookie) && isset($cookie[$key])) {
                $html[] = '<b>' . $key . '=</b>' . $cookie[$key];
            }
        }
        return implode('<br/>', $html);
    }

    /**
     * @return string
     */
    private function getMemoryUsage()
    {
        $bytes = memory_get_peak_usage();
        if ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } else {
            $bytes = $bytes . ' b';
        }
        return $bytes;
    }

    /**
     * @return float
     */
    private function getTimer()
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (microtime(true)) - $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            return (microtime(true)) - $this->timer;
        }
    }

    /**
     * @return string
     */
    private function prepareToolbar()
    {
        $id = session_id();

        $html = '<style>
		#proxy' . $id . '_hdr {
			position: fixed;
			height: auto;
			width: 100%;
			background: #210606;
			color: #c0c0c0;
			padding: 4px 8px;
			bottom: 0;
			left: 0;
			z-index: 999999999;
			font-size: 12px;
			font-family: "Lucida Console", Monaco, monospace;
			opacity: 0.9;
		}
		#proxy' . $id . '_hdr a {
			color: #fff;
			text-decoration: none;
		}
        #proxy' . $id . '_hdr a:hover {
            text-decoration: underline;
        }
		#proxy' . $id . '_hdr form {
			margin:0;
		}
		#proxy' . $id . '_dbg {
			position: fixed;
			height: 80%;
			width: 300px;
			background: #210606;
			color: #c0c0c0;
			bottom: 44px;
			right: 0;
			padding: 5px;
			z-index: 99999999;
			font-size: 10px;
			font-family: "Lucida Console", Monaco, monospace;
			display: none;
		}
		#proxy' . $id . '_hdr input[type=text] {		
			height: 40px;
			width: 40%;
			background: #0c0202;
			color: #fff;
			padding: 4px;
			font-size: 14px;
			border: 1px solid #c0c0c0;
            font-family: "Lucida Console", Monaco, monospace;
		}
		#proxy' . $id . '_hdr input[type=text]:hover {				
			background: #310e0e;
		}
		#proxy' . $id . '_hdr input[type=submit], #proxy' . $id . '_hdr input[type=button],  #proxy' . $id . '_hdr select {		
			height: 40px;		
			background: #210606;
			color: #fff;
			font-size: 12px;
			border: 1px solid #c0c0c0;
            padding: 2px 8px;
            font-family: "Lucida Console", Monaco, monospace;
		}
		#proxy' . $id . '_hdr input[type=submit]:hover, #proxy' . $id . '_hdr input[type=button]:hover {				
			background: #370b0b;
		}
        .proxy' . $id . '_b {
            font-weight: bold;

        }
		html, body {
			margin-bottom: 60px;
		}
		</style>
		<div id="proxy' . $id . '_hdr">
		<form method="GET">
        <a href="' . self::GITHUB_URL . '" target="_blank">ultra-small-proxy v. ' . self::VERSION . '</a> | 
		<b>[' . $this->method . ']</b> URL: <input title="PAGE URL" type="text" name="u" value="' . $this->url . '" />
		<input title="LOAD PAGE WITH PROXY" type="submit" value="GO!" /> ';

        $selected = ['regex' => '', 'dom' => '', '--' => ''];
        if (isset($selected[$this->rewriteMode])) {
            $selected[$this->rewriteMode] = ' selected';
        } else {
            $selected['--'] = ' selected';
        }
        $html .= '<select title="REWRITE MODE" name="r">
        <option value="regex"' . $selected['regex'] . '>REGEX</option>
        <option value="dom"' . $selected['dom'] . '>DOM XML</option>
        <option value=""' . $selected['--'] . '>--</option>
        </select>';

        $selected = ['GET' => '', 'POST' => ''];
        if (isset($selected[$this->method])) {
            $selected[$this->method] = ' selected';
        }
        $html .= '<select title="REQUEST METHOD" name="m">
        <option value="GET"' . $selected['GET'] . '>GET</option>
        <option value="POST"' . $selected['POST'] . '>POST</option>
        </select>
        <input title="OPEN DEBUG TOOLBAR" type="button" value="DBG" onclick="proxy' . $id . '_dbg()" />&nbsp;&nbsp;';

        if (isset($this->status['http_code'])) {
            $html .= ' <b>HTTP ' . $this->status['http_code'] . '</b>';
        }

        $html .= ' / ' . number_format($this->getTimer(), 2) . 's';
        $html .= ' / ' . $this->getMemoryUsage();

        $html .= '</form>
		<div id="proxy' . $id . '_dbg">';

        if (isset($this->status['http_code'])) {
            $html .= '<span class="proxy' . $id . '_b">[HTTP ' . $this->status['http_code'] . ']</span>' . self::TOOLBAR_SEPARATOR;
        }

        if ($this->isError()) {
            $html .= '<span class="proxy' . $id . '_b">[ERRORS: ' . count($this->errorMessages) . ']</span><br/>';
            if ($this->error > 0) {
                $html .= '[CURL ERROR NO ' . $this->error . ']<br/>';
            }
            $html .= implode(self::TOOLBAR_SEPARATOR, $this->errorMessages) . self::TOOLBAR_SEPARATOR;
        }

        if (isset($this->status['redirect_count']) && !empty($this->status['redirect_count'])) {
            $html .= '<span class="proxy' . $id . '_b">REDIRECTS:</span> ' . $this->status['redirect_count'] . self::TOOLBAR_SEPARATOR;
        }

        if (isset($this->headers['content-type'][0])) {
            $html .= '<span class="proxy' . $id . '_b">CONTENT-TYPE</span><br/>';
            $html .= $this->headers['content-type'][0] . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->localCookies) && isset($this->localCookies['PHPSESSID'])) {
            $html .= '<span class="proxy' . $id . '_b">PROXIED PHPSESSID</span><br/>';
            $html .= $this->localCookies['PHPSESSID']['PHPSESSID'] . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($_POST)) {
            $html .= '<span class="proxy' . $id . '_b">RECEIVED/REDIRECTED POST VARS: ' . count($_POST) . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->siteCookies)) {
            $html .= '<span class="proxy' . $id . '_b">COOKIES - RECEIVED FROM ' . $this->domain . '</span><br/>';
            $html .= $this->showCookies($this->siteCookies) . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->localCookies)) {
            $html .= '<span class="proxy' . $id . '_b">COOKIES - SENT TO ' . $this->domain . '</span><br/>';
            $html .= $this->showCookies($this->localCookies);
        }

        $html .= '</div>
		</div>
		<script>
		function proxy' . $id . '_dbg(); {
			var dbg = document.getElementById("proxy' . $id . '_dbg");
			if (dbg.style.display != "block") {
				dbg.style.display = "block";
			} else {
				dbg.style.display = "none";
			}
		}
		</script>';

        return $html;
    }

    /**
     * @return bool|string
     */
    private function connect()
    {
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->url,
            CURLOPT_REFERER => $this->url,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_POST => false,
            CURLOPT_MAXREDIRS => $this->maxRedirections,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    return $len;
                }
                $this->headers[strtolower(trim($header[0]))][] = trim($header[1]);
                return $len;
            }
        ];

        if ($this->method == 'POST') {
            $options[CURLOPT_POST] = true;
            if (!empty($_POST)) {
                $postVars = [];
                foreach ($_POST as $k => $v) {
                    $postVars[] = $k . '=' . $v;
                }
                $options[CURLOPT_POSTFIELDS] = implode('&', $postVars);
            }
        }

        $headerCookies = [];
        if (!empty($this->localCookies)) {
            foreach ($this->localCookies as $cookie) {
                if (is_array($cookie)) {
                    $key = $this->arrayKeyFirst($cookie);
                    $headerCookies[] = $key . '=' . $cookie[$key];
                }
            }
            $options[CURLOPT_COOKIE] = implode(';', $headerCookies);
        }

        $ch = curl_init($this->url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $this->error = curl_errno($ch);
        if ($this->error > 0) {
            $this->isError = true;
            $this->errorMessages[] = curl_error($ch);
        }
        $this->status = curl_getinfo($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->isError;
    }

    /**
     * @return bool
     */
    public function isCurlError()
    {
        if ($this->error > 0) {
            return true;
        }
    }

    /**
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getLocalCookies()
    {
        return $this->localCookies;
    }

    /**
     * @return array
     */
    public function getSiteCookies()
    {
        return $this->siteCookies;
    }

    /**
     * @return string|null
     */
    public function getSid()
    {
        if (!empty($this->localCookies) && isset($this->localCookies['PHPSESSID'])) {
            return $this->localCookies['PHPSESSID']['PHPSESSID'];
        }
    }

    /**
     * @param $userAgent
     * @return string
     */
    public function setUserAgent($userAgent = 'Mozilla/4.0 (compatible;)')
    {
        $this->userAgent = $userAgent;
        return $this->userAgent;
    }

    /**
     * @param $rewriteMode
     * @return string
     */
    public function setRewriteMode($rewriteMode = 'regex')
    {
        $this->rewriteMode = $rewriteMode;
        return $this->rewriteMode;
    }

    /**
     * @param $method
     * @return string
     */
    public function setMethod($method = 'GET')
    {
        $this->method = strtoupper($method);
        return $this->method;
    }

    /**
     * @param $cookiesDir
     * @return string
     */
    public function setCookiesDir($cookiesDir = '')
    {
        if (!empty($cookiesDir)) {
            $this->cookiesDir = rtrim($cookiesDir, '/');
        }
        return $this->cookiesDir;
    }

    /**
     * @param $path
     * @param $prefix
     * @return bool
     */
    private function isPrefixed($path, $prefix)
    {
        if (strpos($path, $prefix) === 0) {
            return true;
        }
    }

    /**
     * @param $path
     * @return bool
     */
    private function isPrefixedAll($path)
    {
        if ($this->isPrefixed(trim($path), 'http')
            || $this->isPrefixed(trim($path), '//')) {
            return true;
        }
    }

    /**
     * @param $path
     * @return string|null
     */
    private function cleanPath($path)
    {
        return preg_replace(['#^./#', '#^//#', '#^/#'], '', $path);
    }

    /**
     * @param $path
     * @param $string
     * @return string
     */
    private function replaceWithPrefix($path, $string)
    {
        return str_replace($path, $this->addPrefix($path), $string);
    }

    /**
     * @param $path
     * @return string
     */
    private function addPrefix($path)
    {
        return parse_url($this->url, PHP_URL_SCHEME) . '://' . $this->domain . '/' . $this->cleanPath($path);
    }

    /**
     * @param $url
     * @return string
     */
    private function generateUrl($url)
    {
        $newUrl = '';
        $newUrl .= '?m=' . $this->method;
        $newUrl .= '&r=' . $this->rewriteMode;
        $newUrl .= '&u=' . urlencode($url);
        return $newUrl;
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackLocation(&$match)
    {
        if (!$this->isPrefixedAll($match[1])) {
            return str_replace($match[1], $this->generateUrl($this->addPrefix($match[1])), $match[0]);
        } else {
            return str_replace($match[1], $this->generateUrl($match[1]), $match[0]);
        }
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackSource(&$match)
    {
        if (!$this->isPrefixedAll($match[1])) {
            return $this->replaceWithPrefix($match[1], $match[0]);
        } else {
            return $match[0];
        }
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackSourceCss(&$match)
    {
        if (strpos($match[1], '.css') !== false) {
            if (!$this->isPrefixedAll($match[1])) {
                return str_replace($match[1], $this->addPrefix($match[1]), $match[0]);
            } else {
                return str_replace($match[1], $match[1], $match[0]);
            }

        } else {
            return $match[0];
        }
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackSourceVideo(&$match)
    {
        if (strpos($match[0], 'type="video') !== false) {
            if (!$this->isPrefixedAll($match[1])) {
                return str_replace($match[1], $this->addPrefix($match[1]), $match[0]);
            } else {
                return $match[1];
            }

        } else {
            return $match[0];
        }
    }

    /**
     * @param $data
     * @return string
     */
    private function rewriteWithRegularExpressions(&$data)
    {
        if (empty($data)) return;

        $patterns = [];
        $patterns['img'] = '#<img.+?src="([^"]*)".*?/?>#is';
        $patterns['script'] = '#<script.+?src="([^"]*)".*?/?>#is';
        $patterns['a'] = '#<a.+?href="([^"]*)".*?/?>#is';
        $patterns['form'] = '#<form.+?action="([^"]*)".*?/?>#is';
        $patterns['css'] = '#<link.+?href="([^"]*)".*?/?>#is';
        $patterns['source'] = '#<source.+?src="([^"]*)".*?/?>#is';

        $data = preg_replace_callback($patterns['img'], [$this, 'replaceCallbackSource'], $data);
        $data = preg_replace_callback($patterns['script'], [$this, 'replaceCallbackSource'], $data);
        $data = preg_replace_callback($patterns['a'], [$this, 'replaceCallbackLocation'], $data);
        $data = preg_replace_callback($patterns['form'], [$this, 'replaceCallbackLocation'], $data);
        $data = preg_replace_callback($patterns['css'], [$this, 'replaceCallbackSourceCss'], $data);
        $data = preg_replace_callback($patterns['source'], [$this, 'replaceCallbackSourceVideo'], $data);

        return $data;
    }

    /**
     * @param $data
     * @return string
     */
    private function rewriteWithDom(&$data)
    {
        if (empty($data)) return;

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->recover = TRUE;
        $dom->loadHTML($data);

        $errorsCount = count(libxml_get_errors());
        if ($errorsCount > 0) {
            $this->isError = true;
            $this->errorMessages[] = 'DOM XML Parse errors: ' . $errorsCount;
        }

        foreach ($dom->getElementsByTagName('a') as $tag) {
            $attribute = $tag->getAttribute('href');
            if (!$this->isPrefixedAll($attribute)) {
                $prefixed = $this->generateUrl($this->addPrefix($attribute));
            } else {
                $prefixed = $this->generateUrl($attribute);
            }
            $tag->setAttribute('href', $prefixed);
        }

        foreach ($dom->getElementsByTagName('img') as $tag) {
            $attribute = $tag->getAttribute('src');
            if (!$this->isPrefixedAll($attribute)) {
                $prefixed = $this->addPrefix($attribute);
            } else {
                $prefixed = $attribute;
            }
            $tag->setAttribute('src', $prefixed);
        }

        foreach ($dom->getElementsByTagName('script') as $tag) {
            $attribute = $tag->getAttribute('src');
            if (!$this->isPrefixedAll($attribute)) {
                $prefixed = $this->addPrefix($attribute);
            } else {
                $prefixed = $attribute;
            }
            $tag->setAttribute('src', $prefixed);
        }

        foreach ($dom->getElementsByTagName('form') as $tag) {
            $attribute = $tag->getAttribute('action');
            if (!$this->isPrefixedAll($attribute)) {
                $prefixed = $this->generateUrl($this->addPrefix($attribute));
            } else {
                $prefixed = $this->generateUrl($attribute);
            }
            $tag->setAttribute('action', $prefixed);
        }

        foreach ($dom->getElementsByTagName('link') as $tag) {
            $attribute = $tag->getAttribute('href');
            if ($tag->getAttribute('rel') == 'stylesheet' || $tag->getAttribute('type') == 'text/css') {
                if (!$this->isPrefixedAll($attribute)) {
                    $prefixed = $this->addPrefix($attribute);
                } else {
                    $prefixed = $attribute;
                }
                $tag->setAttribute('href', $prefixed);
            }
        }

        foreach ($dom->getElementsByTagName('source') as $tag) {
            $attribute = $tag->getAttribute('src');
            $type = $tag->getAttribute('type');
            if (!empty($type) && strpos($type, 'type="video') !== false) {
                if (!$this->isPrefixedAll($attribute)) {
                    $prefixed = $this->addPrefix($attribute);
                } else {
                    $prefixed = $attribute;
                }
                $tag->setAttribute('src', $prefixed);
            }
        }

        $data = $dom->saveHTML();
        unset($dom);

        return $data;
    }

    /**
     * @param $data
     * @return string
     */
    public function parse($data)
    {
        switch ($this->rewriteMode) {
            case 'regex':
                $this->rewriteWithRegularExpressions($data);
                break;
            case 'dom':
                $this->rewriteWithDom($data);
                break;
        }
        
        return $data;
    }

    /**
     * @param $data
     * @return string|null
     */
    private function parseResponse($data)
    {
        $this->parse($data);
        $this->output = $data;

        if ($this->attachToolbar) {
            $toolbar = $this->prepareToolbar();
            if (strpos($this->output, '</body>') !== false) {
                return str_replace('</body>', $toolbar . '</body>', $this->output);
            } else {
                return $this->output . $toolbar;
            }

        } else {
            return $this->output;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->output;
    }
}