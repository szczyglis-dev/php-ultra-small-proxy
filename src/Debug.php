<?php

namespace Szczyglis\UltraSmallProxy;

/**
 * @package UltraSmallProxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 * @license MIT
 * @version 2.1 | 2022.04.23
 */

class Debug
{
    private $config;
    private $timer;
    private $counters = [];

    /**
     * Debug constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
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
