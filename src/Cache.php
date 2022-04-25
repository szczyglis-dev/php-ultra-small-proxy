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
 * Cache
 * 
 * @package szczyglis/php-ultra-small-proxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @copyright 2022 Marcin Szczyglinski
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 */

class Cache
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
     * Cache constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
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
