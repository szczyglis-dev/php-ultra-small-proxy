<?php

namespace Szczyglis\UltraSmallProxy;

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 2.1 | 2022.04.23
 */

class Config
{
    private $config = [];

    /**
     * Config constructor.
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
