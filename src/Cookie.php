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
 * Cookie
 * 
 * @package szczyglis/php-ultra-small-proxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @copyright 2022 Marcin Szczyglinski
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 */

class Cookie
{
    private $config;
    private $id;
    private $cookies = [];
    private $siteCookies = [];
    private $errors = [];

    /**
     * Cookie constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
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
