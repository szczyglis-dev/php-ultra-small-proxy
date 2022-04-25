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
 * Toolbar
 * 
 * @package szczyglis/php-ultra-small-proxy
 * @author Marcin Szczyglinski <szczyglis@protonmail.com>
 * @copyright 2022 Marcin Szczyglinski
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 * @link https://github.com/szczyglis-dev/php-ultra-small-proxy
 */

class Toolbar
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
     * Toolbar constructor.
     * @param Config $config
     * @param Debug $debug
     */
    public function __construct(Config $config, Debug $debug)
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
        <a href="' . $this->data['_github_url'] . '" target="_blank" title="GITHUB PROJECT\'S PAGE">Ultra Small Proxy v.' . $this->data['_version'] . '</a>&nbsp;&nbsp;';

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