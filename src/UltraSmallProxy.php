<?php

namespace Szczyglis\UltraSmallProxy;

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 2.1 | 2022.04.23
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
    const VERSION = '2.1';

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
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->prepare();

        if (true === $config->get('init')) {
            $this->init();
        }
    }

    public function prepare(): void
    {
        $this->debug = new Debug($this->config);
        $this->parser = new Parser($this->config, $this->debug);
        $this->cookie = new Cookie($this->config);
        $this->cache = new Cache($this->config);
        $this->http = new Http($this->config, $this->cache);
        $this->toolbar = new Toolbar($this->config, $this->debug);
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