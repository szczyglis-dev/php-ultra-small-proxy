<?php

namespace Szczyglis\UltraSmallProxy;

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 2.1 | 2022.04.23
 */

class Parser
{
    private $config;
    private $debug;
    private $url;
    private $ip;
    private $domain;
    private $errors = [];

    /**
     * Parser constructor.
     * @param Config $config
     * @param Debug $debug
     */
    public function __construct(Config $config, Debug $debug)
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