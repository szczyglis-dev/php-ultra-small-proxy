<?php

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 2.0 | 2022.02.23
 */
class UltraSmallProxy
{
    /**
     * @var string
     */
    const GITHUB_URL = 'https://github.com/szczyglis-dev/php-ultra-small-proxy';

    /**
     * @var string
     */
    const VERSION = '2.0';

    /**
     * @var UltraSmallProxyConfig
     */
    public $config;

    /**
     * @var UltraSmallProxyDebug
     */
    public $debug;

    /**
     * @var UltraSmallProxyCookie
     */
    public $cookie;

    /**
     * @var UltraSmallProxyParser
     */
    public $parser;

    /**
     * @var UltraSmallProxyHttp
     */
    public $http;

    /**
     * @var UltraSmallProxyToolbar
     */
    public $toolbar;

    /**
     * @var UltraSmallProxyCache
     */
    public $cache;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $response;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $domain = '';

    /**
     * @var array
     */
    private $errors = [];

    /**
     * UltraSmallProxy Constructor.
     * @param UltraSmallProxyConfig $config
     */
    public function __construct(UltraSmallProxyConfig $config)
    {
        $this->config = $config;

        $this->prepare();

        if (true === $config->get('init')) {
            $this->init();
        }
    }

    public function prepare(): void
    {
        $this->debug = new UltraSmallProxyDebug($this->config);
        $this->parser = new UltraSmallProxyParser($this->config, $this->debug);
        $this->cookie = new UltraSmallProxyCookie($this->config);
        $this->cache = new UltraSmallProxyCache($this->config);
        $this->http = new UltraSmallProxyHttp($this->config, $this->cache);
        $this->toolbar = new UltraSmallProxyToolbar($this->config, $this->debug);
    }

    public function init(): void
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (isset($_GET['u'])) {
            $this->url = urldecode($_GET['u']);
        } else {
            $this->url = urldecode($_SERVER['QUERY_STRING']);
        }

        if (isset($_GET['ip']) && !empty($_GET['ip'])) {
            $this->ip = $_GET['ip'];
        }

        if (isset($_GET['host']) && !empty($_GET['host'])) {
            $this->host = $_GET['host'];
        }

        $this->handleAssets();
    }

    public function handleAssets(): void
    {
        if (true === $this->config->get('raw')) {

            $this->cookie->setId(hash('sha256', session_id() . '_' . $this->domain));

            // get local cookies
            $cookies = $this->cookie->load();

            switch ($this->config->get('source')) {
                // by domain
                case 'domain':
                    $this->http->getAsset($this->url, $cookies);
                    break;
                // by IP + host
                case 'ip':
                    if (!empty($this->host)) {
                        $headers = [
                            'Host: ' . $this->host,
                        ];
                    }
                    $this->domain = parse_url('http://' . $this->host, PHP_URL_HOST);
                    $this->http->setExtraHeaders($headers);
                    $this->http->getAsset($this->ip, $cookies);
                    break;
            }
        }
    }

    /**
     * @param string $url
     * @param bool $force
     * @return string
     */
    public function load(string $url = '', bool $force = false): string
    {
        if (empty($this->url) || $force) {
            $this->url = $url;
        }

        if (!empty($this->url) || !empty($this->ip)) {
            // prepare
            switch ($this->config->get('source')) {
                // by domain
                case 'domain':
                    $this->domain = parse_url($this->url, PHP_URL_HOST);
                    break;
                // by IP + host
                case 'ip':
                    $this->domain = parse_url('http://' . $this->host, PHP_URL_HOST);
                    break;
            }

            $this->cookie->setId(hash('sha256', session_id() . '_' . $this->domain));

            // connect and send local cookies
            $cookies = $this->cookie->load();

            switch ($this->config->get('source')) {
                // by domain
                case 'domain':
                    $this->response = $this->http->connect($this->url, $cookies);
                    break;
                // by IP + host
                case 'ip':
                    if (!empty($this->host)) {
                        $headers = [
                            'Host: ' . $this->host,
                        ];
                    }
                    $this->http->setExtraHeaders($headers);
                    $this->response = $this->http->connect($this->ip, $cookies);
                    break;
            }

            // get response headers and receive cookies
            $headers = $this->http->getHeaders();
            if (isset($headers['set-cookie'])) {
                $this->cookie->save($headers['set-cookie']);
            }
        }

        return $this->render($this->response);
    }

    /**
     * @param string $data
     * @return string
     */
    public function render(string &$data): string
    {
        // parse response HTML
        $this->parser->setUrl($this->url);
        $this->parser->setIp($this->ip);
        $this->parser->setDomain($this->domain);
        $this->parser->parse($data);
        $this->output = $data;

        // merge all errors
        $this->handleErrors();

        if (true === $this->config->get('toolbar')) {

            // prepare toolbar data
            $toolbar = $this->toolbar->generate([
                '_github_url' => self::GITHUB_URL,
                '_version' => self::VERSION,
                'url' => $this->url,
                'domain' => $this->domain,
                'ip' => $this->ip,
                'host' => $this->host,
                'session_id' => session_id(),
                'status' => $this->http->getStatus(),
                'headers' => $this->http->getHeaders(),
                'extra_headers' => $this->http->getExtraHeaders(),
                'local_cookies' => $this->cookie->getLocalCookies(),
                'site_cookies' => $this->cookie->getSiteCookies(),
                'errors' => $this->errors,
                'memory' => $this->debug->getMemoryUsage(),
                'timer' => $this->debug->getTimer(),
            ]);

            // append toolbar to body
            if (false !== strpos($this->output, '</body>')) {
                return (string)str_replace('</body>', $toolbar . '</body>', $this->output);
            } else {
                return (string)$this->output . $toolbar;
            }

            // without toolbar
        } else {
            return (string)$this->output;
        }
    }

    /**
     * @return void
     */
    private function handleErrors(): void
    {
        $this->errors += $this->http->getErrors();
        $this->errors += $this->cache->getErrors();
        $this->errors += $this->cookie->getErrors();
        $this->errors += $this->parser->getErrors();
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        if (!empty($this->errors)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string|null
     */
    public function getSid(): ?string
    {
        $cookies = $this->cookie->load();
        if (!empty($cookies) && isset($cookies['PHPSESSID'])) {
            return $cookies['PHPSESSID']['PHPSESSID'];
        }
        return null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->output;
    }
}


/**
 * Class UltraSmallProxyCache
 */
class UltraSmallProxyCache
{
    private $config;
    private $url;
    private $ip;
    private $host;
    private $cookies = [];
    private $headers = [];
    private $extraHeaders = [];
    private $status = [];
    private $errors = [];

    /**
     * UltraSmallProxyCache constructor.
     * @param UltraSmallProxyConfig $config
     */
    public function __construct(UltraSmallProxyConfig $config)
    {
        $this->config = $config;

        $this->init();
    }

    public function init(): void
    {
        if (!is_dir($this->config->get('cache_dir'))) {
            if (mkdir($this->config->get('cache_dir')) === false) {
                $this->errors[] = 'Error creating "' . $this->config->get('cache_dir') . '"" directory (permissions denied issue?)';
            }
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public function getDir(string $url): string
    {
        $dir = hash('sha256', parse_url($url, PHP_URL_HOST));
        return rtrim($this->config->get('cache_dir'), '/') . DIRECTORY_SEPARATOR . $dir;
    }

    /**
     * @param string $url
     * @param bool $mkdir
     * @return bool|string
     */
    public function getPath(string $url, bool $mkdir = true)
    {
        $dir = $this->getDir($url);
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $file = md5($url) . '_' . sha1(basename($url));
        if (!empty($ext)) {
            $file .= '.' . $ext;
        }

        if (!is_dir($dir) && $mkdir === true) {
            if (mkdir($dir) === false) {
                $this->errors[] = 'Error creating "' . $dir . '"" directory (permissions denied issue?)';
                return false;
            }
        }

        return $dir . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param string $url
     * @param array $cookies
     * @return bool|string
     */
    public function store(string $url, array $cookies = [])
    {
        set_time_limit(0);

        $path = $this->getPath($url);
        if (false === $path) {
            return false;
        }

        $fp = fopen($path, 'w+');
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_REFERER => $url,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->config->get('timeout'),
            CURLOPT_TIMEOUT => $this->config->get('timeout'),
            CURLOPT_CUSTOMREQUEST => $this->config->get('method'),
            CURLOPT_USERAGENT => $this->config->get('user_agent'),
            CURLOPT_FILE => $fp,
            CURLOPT_POST => false,
            CURLOPT_MAXREDIRS => $this->config->get('max_redirects'),
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

        // headers to sent
        if (!empty($this->extraHeaders)) {
            $options[CURLOPT_HTTPHEADER] = $this->extraHeaders;
        }

        // htaccess
        if (!empty($this->config->get('htaccess_user')) && !empty($this->config->get('htaccess_pass'))) {
            $options[CURLOPT_USERPWD] = $this->config->get('htaccess_user') . ':' . $this->config->get('htaccess_pass');
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        // post request
        if ($this->config->get('method') == 'POST') {
            $options[CURLOPT_POST] = true;
            if (!empty($_POST)) {
                $postVars = [];
                foreach ($_POST as $k => $v) {
                    $postVars[] = $k . '=' . $v;
                }
                $options[CURLOPT_POSTFIELDS] = implode('&', $postVars);
            }
        }

        // cookies to sent
        $headerCookies = [];
        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                if (is_array($cookie)) {
                    $key = array_key_first($cookie);
                    $headerCookies[] = $key . '=' . $cookie[$key];
                }
            }
            $options[CURLOPT_COOKIE] = implode(';', $headerCookies);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $error = curl_errno($ch);
        if ($error > 0) {
            $this->errors[] = curl_error($ch);
        }
        $this->status = curl_getinfo($ch);
        curl_close($ch);
        fclose($fp);

        return $path;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function exists(string $url): bool
    {
        return file_exists($this->getPath($url, false));
    }

    /**
     * @param string $url
     * @return string
     */
    public function getUrl(string $url): string
    {
        $path = basename($this->config->get('cache_dir')) . '/' . str_replace($this->config->get('cache_dir'), '', $this->getPath($url, false));
        $q = explode('?', $_SERVER['REQUEST_URI'])[0];
        return 'http://' . $_SERVER['HTTP_HOST'] . '/' . $q . $path;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Class UltraSmallProxyConfig
 */
class UltraSmallProxyConfig
{
    private $config = [];

    /**
     * UltraSmallProxyConfig constructor.
     * @param bool $init
     */
    public function __construct(bool $init = true)
    {
        // init defaults
        $this->config = [
            'init' => $init,
            'source' => 'domain',
            'raw' => false,
            'toolbar' => true,
            'user_agent' => 'Mozilla/4.0 (compatible;)',
            'timeout' => 120,
            'max_redirects' => 10,
            'cookies_dir' => __DIR__ . '/cookies',
            'cache_dir' => __DIR__ . '/cache',
            'method' => $_SERVER['REQUEST_METHOD'],
            'rewrite' => 'REGEX2',
            'rewrite_url' => true,
            'rewrite_img' => true,
            'rewrite_js' => true,
            'rewrite_form' => true,
            'rewrite_css' => true,
            'rewrite_video' => true,
            'rewrite_ip' => true,
            'assets' => 'REDIRECT',
            'is_cfg' => false,
            'is_dbg' => false,
            'htaccess_user' => '',
            'htaccess_pass' => '',
        ];

        if (true === $init) {
            $this->init();
        }
    }

    public function init(): void
    {
        // rewrite mode
        if (isset($_GET['r'])) {
            if (empty($_GET['r']) || in_array($_GET['r'], ['REGEX', 'REGEX2', 'REGEX3', 'DOM'])) {
                $this->config['rewrite'] = $_GET['r'];
            }
        }

        // assets download mode
        if (isset($_GET['a'])) {
            if (in_array($_GET['a'], ['REDIRECT', 'CURL'])) {
                $this->config['assets'] = $_GET['a'];
            }
        }

        // request method
        if (isset($_GET['m'])) {
            if (in_array($_GET['m'], ['GET', 'POST'])) {
                $this->config['method'] = $_GET['m'];
                if ($_GET['m'] === 'GET' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->config['method'] = 'POST';
                }
            }
        }

        // source
        if (isset($_GET['s'])) {
            if (in_array($_GET['s'], ['domain', 'ip'])) {
                $this->config['source'] = $_GET['s'];
            }
        }

        // raw
        if (isset($_GET['x'])) {
            if (in_array(intval($_GET['x']), [0, 1])) {
                $this->config['raw'] = (intval($_GET['x']) === 1) ? true : false;
            }
        }

        // auto open cfg
        if (isset($_GET['is_cfg'])) {
            if (in_array(intval($_GET['is_cfg']), [0, 1])) {
                $this->config['is_cfg'] = (intval($_GET['is_cfg']) === 1) ? true : false;
            }
        }

        // auto open dbg
        if (isset($_GET['is_dbg'])) {
            if (in_array(intval($_GET['is_dbg']), [0, 1])) {
                $this->config['is_dbg'] = (intval($_GET['is_dbg']) === 1) ? true : false;
            }
        }

        // assets rewrite
        if (!empty($_GET) && !isset($_GET['r_url'])) {
            $this->config['rewrite_url'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_img'])) {
            $this->config['rewrite_img'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_js'])) {
            $this->config['rewrite_js'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_form'])) {
            $this->config['rewrite_form'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_css'])) {
            $this->config['rewrite_css'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_video'])) {
            $this->config['rewrite_video'] = false;
        }
        if (!empty($_GET) && !isset($_GET['r_ip'])) {
            $this->config['rewrite_ip'] = false;
        }

        // htaccess
        if (isset($_GET['ht_u'])) {
            $this->config['htaccess_user'] = $_GET['ht_u'];
        }
        if (isset($_GET['ht_p'])) {
            $this->config['htaccess_pass'] = $_GET['ht_p'];
        }
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }
}

/**
 * Class UltraSmallProxyCookie
 */
class UltraSmallProxyCookie
{
    private $config;
    private $id;
    private $cookies = [];
    private $siteCookies = [];
    private $errors = [];

    /**
     * UltraSmallProxyCookie constructor.
     * @param UltraSmallProxyConfig $config
     */
    public function __construct(UltraSmallProxyConfig $config)
    {
        $this->config = $config;

        $this->init();
    }

    public function init(): void
    {
        if (!is_dir($this->config->get('cookies_dir'))) {
            if (mkdir($this->config->get('cookies_dir')) === false) {
                $this->errors[] = 'Error creating "' . $this->config->get('cookies_dir') . '"" directory (permissions denied issue?)';
            }
        }
    }

    /**
     * @return array
     */
    public function load(): array
    {
        if (file_exists(rtrim($this->config->get('cookies_dir'), '/') . '/' . $this->id)) {
            $data = file_get_contents(rtrim($this->config->get('cookies_dir'), '/') . '/' . $this->id);
            if (!empty($data)) {
                $this->cookies = unserialize($data);
                return $this->cookies;
            }
        }

        return [];
    }

    /**
     * @param $cookie
     * @return array
     */
    private function parse(string $cookie): array
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
    private function parseAll(array $cookies): array
    {
        $data = [];
        foreach ($cookies as $cookie) {
            if (!empty($cookie)) {
                $parsed = $this->parse($cookie);
                $key = array_key_first($parsed);
                $data[$key] = $parsed;
            }
        }
        return $data;
    }

    /**
     * @param $cookies
     * @return array
     */
    public function save(array $cookies): array
    {
        $this->siteCookies = $this->parseAll($cookies);
        foreach ($this->siteCookies as $key => $cookie) {
            $this->cookies[$key] = $cookie;
        }
        $serialized = serialize($this->cookies);
        $path = rtrim($this->config->get('cookies_dir'), '/') . '/' . $this->id;
        if (file_put_contents($path, $serialized) === false) {
            $this->errors[] = 'Error saving cookies file: ' . $path;
        }

        return $this->siteCookies;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setSiteCookies(array $cookies): self
    {
        $this->siteCookies = $cookies;

        return $this;
    }

    /**
     * @return array
     */
    public function getSiteCookies(): array
    {
        return $this->siteCookies;
    }

    /**
     * @return array
     */
    public function getLocalCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Class UltraSmallProxyDebug
 */
class UltraSmallProxyDebug
{
    private $config;
    private $timer;
    private $counters = [];

    /**
     * UltraSmallProxyDebug constructor.
     * @param UltraSmallProxyConfig $config
     */
    public function __construct(UltraSmallProxyConfig $config)
    {
        $this->config = $config;

        $this->init();
    }

    public function init(): void
    {
        $this->timer = microtime(true);
    }

    /**
     * @return string
     */
    public function getMemoryUsage(): string
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
    public function getTimer(): float
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (microtime(true)) - $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            return (microtime(true)) - $this->timer;
        }
    }

    /**
     * @param string $name
     * @param int $value
     */
    public function addCounter(string $name, int $value = 1): void
    {
        if (isset($this->counters[$name])) {
            $this->counters[$name] += $value;
        } else {
            $this->counters[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @return int
     */
    public function getCounter(string $name): int
    {
        if (isset($this->counters[$name])) {
            return $this->counters[$name];
        } else {
            return 0;
        }
    }
}

/**
 * Class UltraSmallProxyHttp
 */
class UltraSmallProxyHttp
{
    private $config;
    private $cache;
    private $extraHeaders = [];
    private $headers = [];
    private $status = [];
    private $errors = [];

    /**
     * UltraSmallProxyHttp constructor.
     * @param UltraSmallProxyConfig $config
     * @param UltraSmallProxyCache $cache
     */
    public function __construct(UltraSmallProxyConfig $config, UltraSmallProxyCache $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * @param string $url
     * @param array $cookies
     */
    public function getAsset(string $url, array $cookies = []): void
    {
        switch ($this->config->get('assets')) {
            case 'REDIRECT':
                header('Location: ' . $url);
                exit;
                break;
            case 'CURL':
                if (!$this->cache->exists($url)) {
                    if (false !== $this->cache->store($url, $cookies)) {
                        $location = $this->cache->getUrl($url);
                        header('Location: ' . $location);
                        exit;
                    }
                } else {
                    $location = $this->cache->getUrl($url);
                    header('Location: ' . $location);
                    exit;
                }
                break;
        }
    }

    /**
     * @param string $url
     * @param array $cookies
     * @return bool|string
     */
    public function connect(string $url, array $cookies = [])
    {
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_REFERER => $url,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_CONNECTTIMEOUT => $this->config->get('timeout'),
            CURLOPT_TIMEOUT => $this->config->get('timeout'),
            CURLOPT_CUSTOMREQUEST => $this->config->get('method'),
            CURLOPT_USERAGENT => $this->config->get('user_agent'),
            CURLOPT_POST => false,
            CURLOPT_MAXREDIRS => $this->config->get('max_redirects'),
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

        // headers to sent
        if (!empty($this->extraHeaders)) {
            $options[CURLOPT_HTTPHEADER] = $this->extraHeaders;
        }

        // htaccess
        if (!empty($this->config->get('htaccess_user')) && !empty($this->config->get('htaccess_pass'))) {
            $options[CURLOPT_USERPWD] = $this->config->get('htaccess_user') . ':' . $this->config->get('htaccess_pass');
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        // post request
        if ($this->config->get('method') == 'POST') {
            $options[CURLOPT_POST] = true;
            if (!empty($_POST)) {
                $postVars = [];
                foreach ($_POST as $k => $v) {
                    $postVars[] = $k . '=' . $v;
                }
                $options[CURLOPT_POSTFIELDS] = implode('&', $postVars);
            }
        }

        // cookies to sent
        $headerCookies = [];
        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                if (is_array($cookie)) {
                    $key = array_key_first($cookie);
                    $headerCookies[] = $key . '=' . $cookie[$key];
                }
            }
            $options[CURLOPT_COOKIE] = implode(';', $headerCookies);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_errno($ch);
        if ($error > 0) {
            $this->errors[] = curl_error($ch);
        }
        $this->status = curl_getinfo($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getExtraHeaders(): array
    {
        return $this->extraHeaders;
    }

    /**
     * @return mixed
     */
    public function getStatus(): array
    {
        return $this->status;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setExtraHeaders(array $headers = []): self
    {
        $this->extraHeaders = $headers;

        return $this;
    }
}

/**
 * Class UltraSmallProxyParser
 */
class UltraSmallProxyParser
{
    private $config;
    private $debug;
    private $url;
    private $ip;
    private $domain;
    private $errors = [];

    /**
     * UltraSmallProxyParser constructor.
     * @param UltraSmallProxyConfig $config
     * @param UltraSmallProxyDebug $debug
     */
    public function __construct(UltraSmallProxyConfig $config, UltraSmallProxyDebug $debug)
    {
        $this->config = $config;
        $this->debug = $debug;
    }

    /**
     * @param $path
     * @param $prefix
     * @return bool
     */
    private function isPrefixed(string $path, string $prefix): bool
    {
        if (strpos($path, $prefix) === 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $path
     * @return bool
     */
    private function isPrefixedAll(string $path): bool
    {
        if ($this->isPrefixed(trim($path), 'http')) {
            return true;
        }
        return false;
    }

    /**
     * @param $path
     * @return string|null
     */
    private function cleanPath(string $path): string
    {
        return preg_replace(['#^./#', '#^//#', '#^/#'], '', $path);
    }

    /**
     * @param $path
     * @param $string
     * @return string
     */
    private function replaceWithPrefix(string $path, string $string): string
    {
        return str_replace($path, $this->addPrefix($path), $string);
    }

    /**
     * @param $path
     * @return string
     */
    private function addPrefix(string $path): string
    {
        return parse_url($this->url, PHP_URL_SCHEME) . '://' . $this->domain . '/' . $this->cleanPath($path);
    }

    /**
     * @param string $url
     * @param bool $raw
     * @return string
     */
    private function generateUrl(string $url, bool $raw = false): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            $path = '';
            if (preg_match('#^\./#', $url)) {
                $path = parse_url($this->url, PHP_URL_PATH) . '/';
                $url = preg_replace('#^\./#', '', $url);
            }
            $url = 'https://' . $this->domain . $path . $url;
            $host = $this->domain;
        }

        $q = '';
        $q .= '?m=' . $this->config->get('method');
        if (true === $raw) {
            $q .= '&x=1';
        }
        $q .= '&a=' . $this->config->get('assets');
        $q .= '&r=' . $this->config->get('rewrite');
        $q .= '&s=' . $this->config->get('source');
        $q .= '&host=' . $this->domain;
        $q .= '&is_cfg=' . $this->config->get('is_cfg');
        $q .= '&is_dbg=' . $this->config->get('is_dbg');

        $q .= '&ht_u=' . $this->config->get('htaccess_user');
        $q .= '&ht_p=' . $this->config->get('htaccess_pass');

        if (true === $this->config->get('rewrite_url')) {
            $q .= '&r_url=1';
        }
        if (true === $this->config->get('rewrite_img')) {
            $q .= '&r_img=1';
        }
        if (true === $this->config->get('rewrite_js')) {
            $q .= '&r_js=1';
        }
        if (true === $this->config->get('rewrite_form')) {
            $q .= '&r_form=1';
        }
        if (true === $this->config->get('rewrite_css')) {
            $q .= '&r_css=1';
        }
        if (true === $this->config->get('rewrite_video')) {
            $q .= '&r_video=1';
        }
        if (true === $this->config->get('rewrite_ip')) {
            $q .= '&r_ip=1';
        }

        $u = $host . '|' . $this->domain;
        if ('ip' === $this->config->get('source') && $host == $this->domain) {
            $tmpUrl = $url;
            $this->rewriteIp($tmpUrl);
            $q .= '&ip=' . urlencode($tmpUrl);
        } else {
            $q .= '&ip=' . urlencode($this->ip);
        }

        $q .= '&u=' . urlencode($url);
        return $q;
    }

    /**
     * @param bool $raw
     * @return string
     */
    private function addUrl(bool $raw = false): string
    {
        $q = '';
        $q .= '?m=' . $this->config->get('method');
        if (true === $raw) {
            $q .= '&x=1';
        }
        $q .= '&a=' . $this->config->get('assets');
        $q .= '&r=' . $this->config->get('rewrite');
        $q .= '&s=' . $this->config->get('source');
        $q .= '&ip=' . $this->ip;
        $q .= '&host=' . $this->domain;
        $q .= '&is_cfg=' . $this->config->get('is_cfg');
        $q .= '&is_dbg=' . $this->config->get('is_dbg');

        $q .= '&ht_u=' . $this->config->get('htaccess_user');
        $q .= '&ht_p=' . $this->config->get('htaccess_pass');

        if (true === $this->config->get('rewrite_url')) {
            $q .= '&r_url=1';
        }
        if (true === $this->config->get('rewrite_img')) {
            $q .= '&r_img=1';
        }
        if (true === $this->config->get('rewrite_js')) {
            $q .= '&r_js=1';
        }
        if (true === $this->config->get('rewrite_form')) {
            $q .= '&r_form=1';
        }
        if (true === $this->config->get('rewrite_css')) {
            $q .= '&r_css=1';
        }
        if (true === $this->config->get('rewrite_video')) {
            $q .= '&r_video=1';
        }
        if (true === $this->config->get('rewrite_ip')) {
            $q .= '&r_ip=1';
        }

        $q .= '&u=';
        return $q;
    }

    /**
     * @param array $match
     * @return string
     */
    private function norewriteCallback(array $match): string
    {
        if (!$this->isPrefixedAll($match[1])) {
            return str_replace($match[1], $this->addPrefix($match[1]), $match[0]);
        } else {
            return $match[0];
        }
    }

    /**
     * @param array $match
     * @param string $element
     * @return string
     */
    private function rewriteCallback(array $match, string $element): string
    {
        $raw = true;
        switch ($element) {
            case 'url':
                if (false === $this->config->get('rewrite_url')) {
                    return $this->norewriteCallback($match);
                }
                $raw = false;
                break;
            case 'img':
                if (false === $this->config->get('rewrite_img')) {
                    return $this->norewriteCallback($match);
                }
                break;
            case 'js':
                if (false === $this->config->get('rewrite_js')) {
                    return $this->norewriteCallback($match);
                }
                break;
            case 'form':
                if (false === $this->config->get('rewrite_form')) {
                    return $this->norewriteCallback($match);
                }
                $raw = false;
                break;
            case 'css':
                if (false === $this->config->get('rewrite_css')) {
                    return $this->norewriteCallback($match);
                }
                break;
            case 'video':
                if (false === $this->config->get('rewrite_video')) {
                    return $this->norewriteCallback($match);
                }
                break;
        }

        $this->debug->addCounter('rewrited_' . $element);

        return str_replace($match[1], $this->generateUrl($match[1], $raw), $match[0]);
    }

    /**
     * @param $match
     * @return string
     */
    private function rewriteCallbackPrepend(array $match): string
    {
        $raw = true;

        $this->debug->addCounter('rewrited_force');

        return str_replace($match[1], $this->generateUrl($match[1], $raw), $match[0]);
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackUrl(array $match): string
    {
        return $this->rewriteCallback($match, 'url');
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackForm(array $match): string
    {
        return $this->rewriteCallback($match, 'form');
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackImg(array $match): string
    {
        return $this->rewriteCallback($match, 'img');
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackJs(array $match): string
    {
        return $this->rewriteCallback($match, 'js');
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackCss(array $match): string
    {
        if (false !== strpos($match[1], '.css')) {
            return $this->rewriteCallback($match, 'css');
        } else {
            return $match[0];
        }
    }

    /**
     * @param $match
     * @return string
     */
    private function replaceCallbackVideo(array $match): string
    {
        if (false !== strpos($match[0], 'type="video')) {
            return $this->rewriteCallback($match, 'video');
        } else {
            return $match[0];
        }
    }

    /**
     * @param string $data
     * @return bool
     */
    private function rewriteWithRegex(string &$data): bool
    {
        if (empty($data)) return false;

        $patterns = [];
        $patterns['img'] = '#<img.+?src="([^"]*)".*?/?>#is';
        $patterns['js'] = '#<script.+?src="([^"]*)".*?/?>#is';
        $patterns['a'] = '#<a.+?href="([^"]*)".*?/?>#is';
        $patterns['form'] = '#<form.+?action="([^"]*)".*?/?>#is';
        $patterns['css'] = '#<link.+?href="([^"]*)".*?/?>#is';
        $patterns['source'] = '#<source.+?src="([^"]*)".*?/?>#is';

        $data = preg_replace_callback($patterns['img'], [$this, 'replaceCallbackImg'], $data);
        $data = preg_replace_callback($patterns['js'], [$this, 'replaceCallbackJs'], $data);
        $data = preg_replace_callback($patterns['a'], [$this, 'replaceCallbackUrl'], $data);
        $data = preg_replace_callback($patterns['form'], [$this, 'replaceCallbackForm'], $data);
        $data = preg_replace_callback($patterns['css'], [$this, 'replaceCallbackCss'], $data);
        $data = preg_replace_callback($patterns['source'], [$this, 'replaceCallbackVideo'], $data);

        return true;
    }

    /**
     * @param $data
     * @return bool
     */
    private function rewriteForce(&$data): bool
    {
        if (empty($data)) return false;

        $patterns = [];
        $patterns['http'] = '#"(http://[^"]+)#is';
        $patterns['https'] = '#"(https://[^"]+)#is';
        $patterns['http2'] = '#\'(http://[^\']+)#is';
        $patterns['https2'] = '#\'(https://[^\']+)#is';

        $data = preg_replace_callback($patterns['http'], [$this, 'rewriteCallbackPrepend'], $data);
        $data = preg_replace_callback($patterns['https'], [$this, 'rewriteCallbackPrepend'], $data);
        $data = preg_replace_callback($patterns['http2'], [$this, 'rewriteCallbackPrepend'], $data);
        $data = preg_replace_callback($patterns['https2'], [$this, 'rewriteCallbackPrepend'], $data);

        return true;
    }

    /**
     * @param string $attribute
     * @return string
     */
    private function norewriteDomElement(string $attribute): string
    {
        if (!$this->isPrefixedAll($attribute)) {
            $attribute = $this->addPrefix($attribute);
        }
        return $attribute;
    }

    /**
     * @param string $attribute
     * @param string $element
     * @param bool $raw
     * @return string
     */
    private function rewriteDomElement(string $attribute, string $element, bool $raw = true): string
    {
        switch ($element) {
            case 'url':
                if (false === $this->config->get('rewrite_url')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
            case 'img':
                if (false === $this->config->get('rewrite_img')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
            case 'js':
                if (false === $this->config->get('rewrite_js')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
            case 'form':
                if (false === $this->config->get('rewrite_form')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
            case 'css':
                if (false === $this->config->get('rewrite_css')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
            case 'video':
                if (false === $this->config->get('rewrite_video')) {
                    return $this->norewriteDomElement($attribute);
                }
                break;
        }

        $this->debug->addCounter('rewrited_' . $element);

        return $this->generateUrl($attribute, $raw);
    }

    /**
     * @param $data
     * @return bool
     */
    private function rewriteWithDom(&$data): bool
    {
        if (empty($data)) return false;

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->recover = TRUE;
        $dom->loadHTML($data);

        $errorsCount = count(libxml_get_errors());
        if ($errorsCount > 0) {
            $this->errors[] = 'DOM XML Parse errors: ' . $errorsCount;
        }

        foreach ($dom->getElementsByTagName('a') as $tag) {
            $attribute = $tag->getAttribute('href');
            $attribute = $this->rewriteDomElement($attribute, 'url', false);
            $tag->setAttribute('href', $attribute);
        }

        foreach ($dom->getElementsByTagName('img') as $tag) {
            $attribute = $tag->getAttribute('src');
            $attribute = $this->rewriteDomElement($attribute, 'img', true);
            $tag->setAttribute('src', $attribute);
        }

        foreach ($dom->getElementsByTagName('script') as $tag) {
            $attribute = $tag->getAttribute('src');
            $attribute = $this->rewriteDomElement($attribute, 'js', true);
            $tag->setAttribute('src', $attribute);
        }

        foreach ($dom->getElementsByTagName('form') as $tag) {
            $attribute = $tag->getAttribute('action');
            $attribute = $this->rewriteDomElement($attribute, 'form', false);
            $tag->setAttribute('action', $attribute);
        }

        foreach ($dom->getElementsByTagName('link') as $tag) {
            $attribute = $tag->getAttribute('href');
            if ($tag->getAttribute('rel') == 'stylesheet' || $tag->getAttribute('type') == 'text/css') {
                $attribute = $this->rewriteDomElement($attribute, 'url', true);
                $tag->setAttribute('href', $attribute);
            }
        }

        foreach ($dom->getElementsByTagName('source') as $tag) {
            $attribute = $tag->getAttribute('src');
            $type = $tag->getAttribute('type');
            if (!empty($type) && strpos($type, 'type="video') !== false) {
                $attribute = $this->rewriteDomElement($attribute, 'video', true);
                $tag->setAttribute('src', $attribute);
            }
        }

        $data = $dom->saveHTML();
        unset($dom);

        return true;
    }

    /**
     * @param array $match
     * @return string
     */
    public function rewriteIpCallback(array $match): string
    {
        $this->debug->addCounter('rewrited_ip');
        return str_replace($match[1], parse_url($this->ip, PHP_URL_HOST), $match[0]);
    }

    /**
     * @param string $data
     * @return bool
     */
    public function rewriteIp(string &$data): bool
    {
        if (false === $this->config->get('rewrite_ip') || empty($this->domain)) {
            return false;
        }
        $patterns['https'] = '#https://(' . $this->domain . ')#is';
        $patterns['http'] = '#http://(' . $this->domain . ')#is';

        $data = preg_replace_callback($patterns['https'], [$this, 'rewriteIpCallback'], $data);
        $data = preg_replace_callback($patterns['http'], [$this, 'rewriteIpCallback'], $data);

        return true;
    }

    /**
     * @param string $data
     */
    public function appendPrefixes(string &$data): void
    {
        $data = preg_replace('#="//#', '="' . $this->addUrl(true) . parse_url($this->url, PHP_URL_SCHEME) . '://', $data);
    }

    /**
     * @param string $data
     * @return void
     */
    public function parse(string &$data): void
    {
        switch ($this->config->get('rewrite')) {
            case 'REGEX':
                $this->rewriteWithRegex($data);
                break;
            case 'REGEX2':
                $this->appendPrefixes($data);
                $this->rewriteWithRegex($data);
                break;
            case 'REGEX3':
                $this->appendPrefixes($data);
                $this->rewriteWithRegex($data);
                $this->rewriteForce($data);
                break;
            case 'DOM':
                $this->rewriteWithDom($data);
                break;
        }

        if ($this->config->get('source') === 'ip') {
            $this->rewriteIp($data);
        }
    }

    /**
     * @param string|null $url
     * @return mixed
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string|null $ip
     * @return mixed
     */
    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @param string|null $domain
     * @return mixed
     */
    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Class UltraSmallProxyToolbar
 */
class UltraSmallProxyToolbar
{
    const TOOLBAR_SEPARATOR = '<div class="hr"></div>';

    private $config;
    private $debug;
    private $id;
    private $data;
    private $headers = [];
    private $status = [];
    private $errors = [];

    /**
     * UltraSmallProxyToolbar constructor.
     * @param UltraSmallProxyConfig $config
     * @param UltraSmallProxyDebug $debug
     */
    public function __construct(UltraSmallProxyConfig $config, UltraSmallProxyDebug $debug)
    {
        $this->config = $config;
        $this->debug = $debug;
    }

    /**
     * @param array $data
     * @return string
     */
    public function generate(array $data = []): string
    {
        $this->id = $data['session_id'];
        $this->data = $data;

        $html = '';
        $html .= $this->appendCss();
        $html .= '<div id="proxy' . $this->id . '_hdr">';
        $html .= '<form method="GET">';
        $html .= $this->appendPrefix();
        $html .= $this->appendInput();
        $html .= $this->appendBtns();
        $html .= $this->appendSuffix();
        $html .= $this->appendCfg();
        $html .= $this->appendHidden();
        $html .= '</form>';
        $html .= $this->appendDebug();
        $html .= '</div>';
        $html .= $this->appendJs();

        return $html;
    }

    /**
     * @param array $cookies
     * @return string
     */
    private function appendCookies(array $cookies): string
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
    private function appendCss(): string
    {
        return '
        <style>
            #proxy' . $this->id . '_hdr .hr {
                margin-top: 15px !important;
                margin-bottom: 15px !important;
                border-bottom: 1px solid #fff !important;
            }
            #proxy' . $this->id . '_hdr {
                position: fixed !important;
                height: auto !important;
                width: 100% !important;
                background: #210606 !important;
                color: #c0c0c0 !important;
                padding: 4px 8px !important;
                bottom: 0 !important;
                left: 0 !important;
                z-index: 999999999 !important;
                font-size: 12px !important;
                font-family: "Lucida Console", Monaco, monospace !important;
                opacity: 0.9 !important;
                transition: opacity .25s ease-in-out !important;
                -moz-transition: opacity .25s ease-in-out !important;
                -webkit-transition: opacity .25s ease-in-out !important;
            }
            #proxy' . $this->id . '_hdr:hover {
                opacity: 1 !important;
            }
            #proxy' . $this->id . '_hdr a {
                color: #fff !important;
                text-decoration: none !important;
            }
            #proxy' . $this->id . '_hdr a:hover {
                text-decoration: underline !important;
            }
            #proxy' . $this->id . '_hdr form {
                margin:0;
            }        
            #proxy' . $this->id . '_hdr .errors {
                color: yellow !important;
            }    
            #proxy' . $this->id . '_hdr .input-domain {    
                width: 40% !important;
            }
            #proxy' . $this->id . '_hdr .input-domain input[type=text] {        
                height: 32px !important;
                width: 95% !important;
                background: #0c0202 !important;
                color: #fff !important;
                padding: 4px !important;
                padding-left: 10px !important;
                font-size: 12px !important;
                border: 1px solid #c0c0c0 !important;
                font-family: "Lucida Console", Monaco, monospace !important;
            }
            #proxy' . $this->id . '_hdr .input-ip {    
                width: 40% !important;
            }
            #proxy' . $this->id . '_hdr .input-ip input[type=text] {      
                height: 32px !important;
                width: 45% !important;
                background: #0c0202 !important;
                color: #fff !important;
                padding: 4px !important;
                padding-left: 10px !important;
                font-size: 12px !important;
                border: 1px solid #c0c0c0 !important;
                font-family: "Lucida Console", Monaco, monospace !important;
            }
            #proxy' . $this->id . '_cfg input[type=text] {      
                height: 32px !important;
                width: 90% !important;
                background: #0c0202 !important;
                color: #fff !important;
                padding: 4px !important;
                padding-left: 10px !important;
                font-size: 12px !important;
                border: 1px solid #c0c0c0 !important;
                font-family: "Lucida Console", Monaco, monospace !important;
            }
            #proxy' . $this->id . '_hdr input[type=text]:hover,
            #proxy' . $this->id . '_cfg input[type=text]:hover { 
                background: #310e0e;
            }
            #proxy' . $this->id . '_hdr input[type=submit], 
            #proxy' . $this->id . '_hdr input[type=button],  
            #proxy' . $this->id . '_hdr select {     
                height: 40px !important;       
                background: #210606 !important;
                color: #fff !important;
                border: 1px solid #c0c0c0 !important;
                padding: 2px 8px !important;
                font-size: 12px !important;
                font-family: "Lucida Console", Monaco, monospace !important;
            }
            #proxy' . $this->id . '_hdr input[type=submit]:hover, #proxy' . $this->id . '_hdr input[type=button]:hover, 
            #proxy' . $this->id . '_hdr select:hover {                
                background: #370b0b !important;
                color: orange !important;
            }
            #proxy' . $this->id . '_hdr .svg-icon {        
                display: inline-block !important;
                margin: auto !important;
                vertical-align: middle !important;
            }
            #proxy' . $this->id . '_hdr .svg-icon svg {        
                height: 40px !important;
                width: 40px !important;
            }
            #proxy' . $this->id . '_hdr .svg-icon svg path {        
                fill: #fff !important;
            }
            #proxy' . $this->id . '_hdr .svg-icon:hover svg path {        
                fill: orange !important;
                cursor: pointer !important;
            }
            #proxy' . $this->id . '_dbg {
                position: fixed !important;
                height: auto !important;
                width: 300px !important;
                background: #210606 !important;
                color: #c0c0c0 !important;
                top:0 !important;
                bottom: 53px !important;
                right: 0 !important;
                padding: 5px !important;
                z-index: 99999999 !important;
                font-size: 10px !important;
                font-family: "Lucida Console", Monaco, monospace !important;
                display: none;
                overflow-y: auto !important;
                text-align: left !important;
            }
            #proxy' . $this->id . '_cfg {
                position: fixed !important;
                height: auto !important;
                width: 300px !important;
                background: #210606 !important;
                color: #c0c0c0 !important;
                top:0 !important;
                bottom: 53px !important;
                left: 0 !important;
                padding: 5px !important;
                z-index: 99999999 !important;
                font-size: 10px !important;
                font-family: "Lucida Console", Monaco, monospace !important;
                display: none;
                overflow-y: auto !important;
                text-align: left !important;
            }
            .proxy' . $this->id . '_b {
                font-weight: bold !important;
            }
            .proxy' . $this->id . '_panel_title {
                font-size: 12px;
                text-align: right;
                margin-bottom: 10px;
                color: #fff;
            }
            .proxy' . $this->id . '_panel_title ._x:hover {
                color: orange;
                cursor: pointer;
            }
            html, body {
                margin-bottom: 60px !important;
            }
        </style>';
    }

    /**
     * @return string
     */
    private function appendJs(): string
    {
        return '<script>
            function proxy' . $this->id . '_dbg() {
                var dbg = document.getElementById("proxy' . $this->id . '_dbg");
                var hidden = document.getElementById("proxy' . $this->id . '_hidden_is_dbg");
                if (dbg.style.display != "block") {
                    dbg.style.display = "block";
                    hidden.value = 1;
                } else {
                    dbg.style.display = "none";
                    hidden.value = 0;
                }
            }
            function proxy' . $this->id . '_cfg() {
                var cfg = document.getElementById("proxy' . $this->id . '_cfg");
                var hidden = document.getElementById("proxy' . $this->id . '_hidden_is_cfg");
                if (cfg.style.display != "block") {
                    cfg.style.display = "block";
                    hidden.value = 1;
                } else {
                    cfg.style.display = "none";
                    hidden.value = 0;
                }
            }
            function proxy' . $this->id . '_src() {
                var select = document.getElementById("proxy' . $this->id . '_input_source_change");
                var domain = document.getElementById("proxy' . $this->id . '_input_source_domain");
                var ip = document.getElementById("proxy' . $this->id . '_input_source_ip");
                if (select.value == "domain") {
                    domain.style.display = "inline-block";
                    ip.style.display = "none";
                } else {
                    domain.style.display = "none";
                    ip.style.display = "inline-block";
                }
            }
            </script>';
    }

    /**
     * @return string
     */
    private function appendPrefix(): string
    {
        $html = '';
        $html .= '
        <a href="' . $this->data['_github_url'] . '" target="_blank" title="GITHUB PROJECT\'S PAGE">ultra-small-proxy v.' . $this->data['_version'] . '</a>&nbsp;&nbsp;';

        $selected = [
            'GET' => '',
            'POST' => ''
        ];
        if (isset($selected[$this->config->get('method')])) {
            $selected[$this->config->get('method')] = ' selected';
        }
        $html .= '<select title="REQUEST METHOD" name="m">
        <option value="GET"' . $selected['GET'] . '>GET</option>
        <option value="POST"' . $selected['POST'] . '>POST</option>
        </select>';

        $selected = [
            'domain' => '',
            'ip' => '',
        ];
        if (isset($selected[$this->config->get('source')])) {
            $selected[$this->config->get('source')] = ' selected';
        }
        $html .= '<select title="SOURCE" name="s" onchange="proxy' . $this->id . '_src()" id="proxy' . $this->id . '_input_source_change">
        <option value="domain"' . $selected['domain'] . '>DOMAIN</option>
        <option value="ip"' . $selected['ip'] . '>IP/HOST</option>
        </select>';

        return $html;
    }

    /**
     * @return string
     */
    private function appendInput(): string
    {
        $html = '';
        $display = [
            'domain' => 'none',
            'ip' => 'none',
        ];
        $current = $this->config->get('source');
        if (isset($display[$current])) {
            $display[$current] = 'inline-block';
        }

        $html .= '
            <span class="input-domain" id="proxy' . $this->id . '_input_source_domain" style="display:' . $display['domain'] . '">
                <input placeholder="https://github.com" title="PAGE URL" type="text" name="u" value="' . $this->data['url'] . '" />
            </span>
            <span class="input-ip" id="proxy' . $this->id . '_input_source_ip" style="display:' . $display['ip'] . '">
                <input placeholder="http://140.82.121.3" title="IP" type="text" name="ip" value="' . $this->data['ip'] . '" />
                <input placeholder="github.com" title="HTTP HEADER: Host" type="text" name="host" value="' . $this->data['host'] . '" />
            </span>
            <input title="LOAD PAGE WITH PROXY" type="submit" value="GO!" /> ';

        return $html;
    }

    /**
     * @param string $onclick
     * @return string
     */
    private function appendCloseBtn(string $onclick): string
    {
        return '<span title="[CLOSE]" class="_x" onclick="' . $onclick . '"><b>[x]</b></span>';
    }

    /**
     * @return string
     */
    private function appendBtns(): string
    {
        $html = '';
        $html .= '
        <span title="OPEN CONFIG TOOLBAR" class="svg-icon" onclick="proxy' . $this->id . '_cfg()" >
            <?xml version="1.0" ?><svg enable-background="new 0 0 64 64" height="64px" id="Layer_1" version="1.1" viewBox="0 0 64 64" width="64px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><circle cx="32" cy="32" r="4.167"/><path d="M55.192,27.87l-5.825-1.092c-0.354-1.178-0.818-2.308-1.392-3.371l3.37-4.927c0.312-0.456,0.248-1.142-0.143-1.532   l-4.155-4.156c-0.391-0.391-1.076-0.454-1.532-0.143l-4.928,3.372c-1.094-0.59-2.259-1.063-3.473-1.42l-1.086-5.794   c-0.103-0.543-0.632-0.983-1.185-0.983h-5.877c-0.553,0-1.082,0.44-1.185,0.983l-1.097,5.851c-1.165,0.356-2.282,0.82-3.334,1.392   l-4.866-3.329c-0.456-0.312-1.142-0.248-1.532,0.143l-4.156,4.156c-0.391,0.391-0.454,1.076-0.143,1.532l3.35,4.896   c-0.564,1.052-1.021,2.168-1.371,3.331L8.808,27.87c-0.542,0.103-0.982,0.632-0.982,1.185v5.877c0,0.553,0.44,1.082,0.982,1.185   l5.82,1.091c0.355,1.188,0.823,2.328,1.401,3.399l-3.312,4.842c-0.312,0.456-0.248,1.142,0.143,1.532l4.155,4.156   c0.391,0.391,1.076,0.454,1.532,0.143l4.84-3.313c1.041,0.563,2.146,1.021,3.299,1.375l1.097,5.852   c0.103,0.542,0.632,0.982,1.185,0.982h5.877c0.553,0,1.082-0.44,1.185-0.982l1.086-5.796c1.201-0.354,2.354-0.821,3.438-1.401   l4.902,3.354c0.456,0.312,1.142,0.248,1.532-0.143l4.155-4.154c0.391-0.391,0.454-1.076,0.143-1.532l-3.335-4.874   c0.589-1.084,1.063-2.237,1.423-3.44l5.819-1.091c0.542-0.103,0.982-0.632,0.982-1.185v-5.877   C56.175,28.502,55.734,27.973,55.192,27.87z M32,42.085c-5.568,0-10.083-4.515-10.083-10.086c0-5.567,4.515-10.083,10.083-10.083   c5.57,0,10.086,4.516,10.086,10.083C42.086,37.57,37.569,42.085,32,42.085z"/></g></svg>
        </span>';

        $html .= '
        <span title="OPEN DEBUG TOOLBAR" class="svg-icon" onclick="proxy' . $this->id . '_dbg()" >
            <?xml version="1.0" ?><svg id="icon" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;}</style></defs><title/><path d="M29.83,20l.34-2L25,17.15V13c0-.08,0-.15,0-.23l5.06-1.36-.51-1.93-4.83,1.29A9,9,0,0,0,20,5V2H18V4.23a8.81,8.81,0,0,0-4,0V2H12V5a9,9,0,0,0-4.71,5.82L2.46,9.48,2,11.41,7,12.77c0,.08,0,.15,0,.23v4.15L1.84,18l.32,2L7,19.18a8.9,8.9,0,0,0,.82,3.57L3.29,27.29l1.42,1.42,4.19-4.2a9,9,0,0,0,14.2,0l4.19,4.2,1.42-1.42-4.54-4.54A8.9,8.9,0,0,0,25,19.18ZM15,25.92A7,7,0,0,1,9,19V13h6ZM9.29,11a7,7,0,0,1,13.42,0ZM23,19a7,7,0,0,1-6,6.92V13h6Z"/><rect class="cls-1" height="32" width="32"/></svg>
        </span>&nbsp;&nbsp;';

        return $html;
    }

    /**
     * @return string
     */
    private function appendSuffix(): string
    {
        $html = '';
        if (isset($this->data['status']['http_code'])) {
            $html .= $this->config->get('method') . ' <b>HTTP ' . $this->data['status']['http_code'] . '</b>';
        }
        $html .= ' / ' . number_format($this->data['timer'], 2) . 's';
        $html .= ' / ' . $this->data['memory'];

        return $html;
    }

    /**
     * @return string
     */
    private function appendHidden(): string
    {
        $values = [
            'cfg' => 0,
            'dbg' => 0,
        ];

        if (true === $this->config->get('is_cfg')) {
            $values['cfg'] = 1;
        }
        if (true === $this->config->get('is_dbg')) {
            $values['dbg'] = 1;
        }

        $html = '';
        $html .= '<input type="hidden" name="is_cfg" value="' . $values['cfg'] . '" id="proxy' . $this->id . '_hidden_is_cfg" />';
        $html .= '<input type="hidden" name="is_dbg" value="' . $values['dbg'] . '" id="proxy' . $this->id . '_hidden_is_dbg" />';
        return $html;
    }

    /**
     * @return string
     */
    private function appendDebug(): string
    {
        $display = 'none';
        if (true === $this->config->get('is_dbg')) {
            $display = 'block';
        }

        $html = '<div id="proxy' . $this->id . '_dbg" style="display: ' . $display . '">';
        $html .= '<div class="proxy' . $this->id . '_panel_title">[DEBUG]&nbsp; ' . $this->appendCloseBtn('proxy' . $this->id . '_dbg()') . '</div> ';

        $source = $this->data['domain'];
        if ($this->config->get('source') === 'ip') {
            $source = $this->data['ip'];
        }

        $html .= '<span class="proxy' . $this->id . '_b">URL:</span> ' . $this->data['url'] . '<br/>';
        $html .= '<span class="proxy' . $this->id . '_b">DOMAIN:</span> ' . $this->data['domain'] . '<br/>';
        $html .= '<span class="proxy' . $this->id . '_b">IP:</span> ' . $this->data['ip'] . '<br/>';
        $html .= '<span class="proxy' . $this->id . '_b">HOST:</span> ' . $this->data['host'] . '<br/>';
        $html .= '<span class="proxy' . $this->id . '_b">METHOD:</span> ' . $this->config->get('method') . '<br/>';
        $html .= self::TOOLBAR_SEPARATOR;

        if (isset($this->data['status']['http_code'])) {
            $html .= '<span class="proxy' . $this->id . '_b">[HTTP ' . $this->data['status']['http_code'] . ']</span>' . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->data['errors'])) {
            $html .= '<span class="proxy' . $this->id . '_b errors">[ERRORS: ' . count($this->data['errors']) . ']</span><br/>';
            $html .= '<span class="errors">' . implode(self::TOOLBAR_SEPARATOR, $this->data['errors']) . '</span>' . self::TOOLBAR_SEPARATOR;
        }

        if (isset($this->data['status']['redirect_count']) && !empty($this->data['status']['redirect_count'])) {
            $html .= '<span class="proxy' . $this->id . '_b">REDIRECTS:</span> ' . $this->data['status']['redirect_count'] . self::TOOLBAR_SEPARATOR;
        }

        if (isset($this->data['headers']['content-type'][0])) {
            $html .= '<span class="proxy' . $this->id . '_b">CONTENT-TYPE</span><br/>';
            $html .= $this->data['headers']['content-type'][0] . self::TOOLBAR_SEPARATOR;
        }

        $html .= '<span class="proxy' . $this->id . '_b">REWRITED:</span><br/>';
        $html .= '<b>URLS:</b>' . $this->debug->getCounter('rewrited_url');
        $html .= ', <b>JS:</b>' . $this->debug->getCounter('rewrited_js');
        $html .= ', <b>CSS:</b>' . $this->debug->getCounter('rewrited_css');
        $html .= ', <b>IMG:</b>' . $this->debug->getCounter('rewrited_img');
        $html .= ', <b>VIDEO:</b>' . $this->debug->getCounter('rewrited_video');
        $html .= ', <b>FORM:</b>' . $this->debug->getCounter('rewrited_form');
        $html .= ', <b>DOMAIN->IP:</b>' . $this->debug->getCounter('rewrited_ip');
        $html .= self::TOOLBAR_SEPARATOR;

        if (!empty($this->data['local_cookies']) && isset($this->data['local_cookies']['PHPSESSID'])) {
            $html .= '<span class="proxy' . $this->id . '_b">PROXIED PHPSESSID</span><br/>';
            $html .= $this->data['local_cookies']['PHPSESSID']['PHPSESSID'] . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($_POST)) {
            $html .= '<span class="proxy' . $this->id . '_b">RECEIVED/REDIRECTED POST VARS:</span> ' . count($_POST) . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->data['site_cookies'])) {
            $html .= '<span class="proxy' . $this->id . '_b">COOKIES - RECEIVED FROM ' . $source . '</span><br/>';
            $html .= $this->appendCookies($this->data['site_cookies']) . self::TOOLBAR_SEPARATOR;
        }

        if (!empty($this->data['local_cookies'])) {
            $html .= '<span class="proxy' . $this->id . '_b">COOKIES - SENT TO ' . $source . '</span><br/>';
            $html .= $this->appendCookies($this->data['local_cookies']);
        }

        if (!empty($this->data['extra_headers'])) {
            $html .= self::TOOLBAR_SEPARATOR . '<span class="proxy' . $this->id . '_b">HEADERS [SENT]</span><br/>';
            foreach ($this->data['extra_headers'] as $header) {
                $html .= $header . '<br/>';
            }
        }

        if (!empty($this->data['headers'])) {
            $html .= self::TOOLBAR_SEPARATOR . '<span class="proxy' . $this->id . '_b">HEADERS [RECEIVED]</span><br/>';
            foreach ($this->data['headers'] as $k => $data) {
                $html .= '<span class="proxy' . $this->id . '_b">' . $k . ': </span>';
                foreach ($data as $header) {
                    $html .= $header . '<br/>';
                }
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    private function appendCfg(): string
    {
        $display = 'none';
        if (true === $this->config->get('is_cfg')) {
            $display = 'block';
        }

        $html = '<div id="proxy' . $this->id . '_cfg" style="display: ' . $display . '">';
        $html .= '<div class="proxy' . $this->id . '_panel_title">[OPTIONS]&nbsp; ' . $this->appendCloseBtn('proxy' . $this->id . '_cfg()') . '</div> ';

        $html .= '<span class="proxy' . $this->id . '_b">[REWRITE MODE]</span><br/><br/>';

        $selected = [
            'REGEX' => '',
            'REGEX2' => '',
            'REGEX3' => '',
            'DOM' => '',
            '--' => ''
        ];
        if (isset($selected[$this->config->get('rewrite')])) {
            $selected[$this->config->get('rewrite')] = ' selected';
        } else {
            $selected['--'] = ' selected';
        }
        $html .= '<select title="REWRITE MODE" name="r">
        <option value="REGEX"' . $selected['REGEX'] . '>REGEX</option>
        <option value="REGEX2"' . $selected['REGEX2'] . '>REGEX2</option>
        <option value="REGEX3"' . $selected['REGEX3'] . '>REGEX3</option>
        <option value="DOM"' . $selected['DOM'] . '>DOM XML</option>
        <option value=""' . $selected['--'] . '>DISABLE</option>
        </select>';

        $html .= self::TOOLBAR_SEPARATOR;

        $html .= '<span class="proxy' . $this->id . '_b">[ASSETS DOWNLOAD MODE]</span><br/><br/>';

        $selected = [
            'REDIRECT' => '',
            'CURL' => '',
        ];
        if (isset($selected[$this->config->get('assets')])) {
            $selected[$this->config->get('assets')] = ' selected';
        } else {
            $selected['REDIRECT'] = ' selected';
        }
        $html .= '<select title="ASSETS DOWNLOAD MODE" name="a">
        <option value="REDIRECT"' . $selected['REDIRECT'] . '>REDIRECT (FAST)</option>
        <option value="CURL"' . $selected['CURL'] . '>CURL/CACHE (SLOW)</option>
        </select>';

        $html .= self::TOOLBAR_SEPARATOR;

        $html .= '<span class="proxy' . $this->id . '_b">[REWRITED ELEMENTS]</span><br/><br/>';

        $checked = [
            'url' => '',
            'js' => '',
            'css' => '',
            'img' => '',
            'video' => '',
            'form' => '',
            'ip' => '',
        ];

        foreach (array_keys($checked) as $el) {
            $key = 'rewrite_' . $el;
            if (true === $this->config->get($key)) {
                $checked[$el] = 'checked';
            }
        }
        foreach (array_keys($checked) as $el) {
            $title = strtoupper($el);
            if ($el == 'ip') {
                $title = 'DOMAIN->IP (IP/HOST ONLY)';
            }
            $html .= '<input type="checkbox" name="r_' . $el . '" value="1"' . $checked[$el] . '/> ' . $title . '<br/>';
        }

        $html .= self::TOOLBAR_SEPARATOR;

        $html .= '<span class="proxy' . $this->id . '_b">[HTTP AUTH - IF NEEDED]</span><br/><br/>';

        $html .= $this->appendHtaccessInput();

        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    private function appendHtaccessInput(): string
    {
        $html = '
            <div class="htaccess-inputs">
                <input placeholder="HTTP AUTH: user" title="HTTP AUTH: user" type="text" name="ht_u" value="' . $this->config->get('htaccess_user') . '" /><br/>
                <input placeholder="HTTP AUTH: password" title="HTTP AUTH: password" type="text" name="ht_p" value="' . $this->config->get('htaccess_pass') . '" />
            </div>';

        return $html;
    }
}
