<?php

/**
 * This file is part of szczyglis/php-ultra-small-proxy.
 *
 * (c) Marcin Szczyglinski <szczyglis@protonmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Szczyglis\UltraSmallProxy;

/**
 * Http
 * 
 * @package szczyglis/php-ultra-small-proxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @copyright 2022 Marcin Szczyglinski
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 */

class Http
{
    private $config;
    private $cache;
    private $extraHeaders = [];
    private $headers = [];
    private $status = [];
    private $errors = [];

    /**
     * Http constructor.
     * @param Config $config
     * @param Cache $cache
     */
    public function __construct(Config $config, Cache $cache)
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