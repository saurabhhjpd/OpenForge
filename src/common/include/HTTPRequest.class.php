<?php
/**
 * Copyright (c) Enalean, 2012-2015. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('browser.php');

class HTTPRequest extends Codendi_Request {

    const HEADER_X_FORWARDED_PROTO = 'HTTP_X_FORWARDED_PROTO';
    const HEADER_X_FORWARDED_FOR   = 'HTTP_X_FORWARDED_FOR';
    const HEADER_HOST              = 'HTTP_HOST';
    const HEADER_REMOTE_ADDR       = 'REMOTE_ADDR';

    /**
     * @var array
     */
    private $trusted_proxied;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct($_REQUEST);
    }
    

    /**
     * Get the value of $variable in $this->params (server side values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    public function getFromServer($variable) {
        return $this->_get($variable, $_SERVER);
    }

    /**
     * Check if current request is send via 'post' method.
     *
     * This method is useful to test if the current request comes from a form.
     *
     * @return boolean
     */
    public function isPost() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if browser used to submit the request is netscape 4.
     *
     * @return boolean
     */
    public function browserIsNetscape4() {
        return browser_is_netscape4();
    }

    public function getBrowser() {
        $is_deprecated = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7') !== false;
        $is_ie8        = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0') !== false;
        $is_ie9        = strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/5.0') !== false;
        $is_ie10       = strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/6.0') !== false;
        $is_ie11       = strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0') !== false;

        if ($is_deprecated && ($is_ie9 || $is_ie10 || $is_ie11)) {
            return new BrowserIECompatibilityModeDeprecated();
        } else if ($is_deprecated) {
            return new BrowserIE7Deprecated($this->getCurrentUser());
        } else if ($is_ie8) {
            return new BrowserIE8();
        }

        return new Browser();
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return HTTPRequest
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * Validate file upload.
     *
     * @param  Valid_File Validator for files.
     * @return Boolean
     */
    public function validFile(&$validator) {
        if(is_a($validator, 'Valid_File')) {
            $this->_validated_input[$validator->getKey()] = true;
            return $validator->validate($_FILES, $validator->getKey());
        } else {
            return false;
        }
    }

    /**
     * Remove slashes in $value. If $value is an array, remove slashes for each
     * element.
     *
     * @access private
     * @param mixed $value
     * @return mixed
     */
    protected function _stripslashes($value) {
        if (is_string($value)) {
            $value = stripslashes($value);
        } else if (is_array($value)) {
            foreach($value as $key => $val) {
                $value[$key] = $this->_stripslashes($val);
            }
        }
        return $value;
    }

    /**
     * Get the value of $variable in $array. If magic_quotes are enabled, the
     * value is escaped.
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @param array $array Name of the parameter to get.
     */
    function _get($variable, $array) {
        if ($this->_exist($variable, $array)) {
            return (get_magic_quotes_gpc() ? $this->_stripslashes($array[$variable]) : $array[$variable]);
        } else {
            return false;
        }
    }

    /**
     * Returns true if request is served in HTTPS by current server
     *
     * @return boolean
     */
    public function isSSL() {
        return $this->doWeTerminateSSL();
    }

    private function doWeTerminateSSL() {
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on');
    }

    /**
     * What are the IP adresses trusted to be a proxy
     *
     * @param array $proxies
     */
    public function setTrustedProxies(array $proxies) {
        foreach($proxies as $proxy) {
            $this->trusted_proxied[$proxy] = true;
        }
    }

    /**
     * isSSL version that take into account reverse proxy
     *
     * @return type
     */
    private function isSSLForEndUser() {
        if ($this->reverseProxyForwardsOriginalProtocol()) {
            return $this->isOriginalProtocolSSL();
        }
        return $this->doWeTerminateSSL();
    }

    private function reverseProxyForwardsOriginalProtocol() {
        return $this->isFromTrustedProxy() && isset($_SERVER[self::HEADER_X_FORWARDED_PROTO]);
    }

    private function isFromTrustedProxy() {
        return isset($_SERVER[self::HEADER_REMOTE_ADDR]) && isset($this->trusted_proxied[$_SERVER[self::HEADER_REMOTE_ADDR]]);
    }

    private function isOriginalProtocolSSL() {
        return strtolower($_SERVER[self::HEADER_X_FORWARDED_PROTO]) === 'https';
    }


    private function getScheme() {
        if ($this->isSSLForEndUser()) {
            return 'https://';
        } else {
            return 'http://';
        }
    }

    /**
     * Returns the ServerURL the user requested, taking into account reverse proxy
     * and protocols variations
     *
     * Logic:
     * -> when we detect a reverse proxy, return reverse proxy URL
     *   -> this assume that reverse proxy rewite HOST and HTTP_X_FORWARDED_PROTO headers
     *
     *
     * @return String Fully qualified URL
     */
    public function getServerUrl() {
        if ($this->reverseProxyForwardsOriginalProtocol()) {
            return $this->getScheme().$_SERVER[self::HEADER_HOST];
        } elseif ($this->isSSLForEndUser() && ForgeConfig::get('sys_https_host')) {
            return $this->getScheme().ForgeConfig::get('sys_https_host');
        } else {
            return $this->getScheme().ForgeConfig::get('sys_default_domain');
        }
    }

    /**
     * Return request IP address
     *
     * When run behind a reverse proxy, REMOTE_ADDR will be the IP address of the
     * reverse proxy, use this method if you want to get the actual ip address
     * of the request without having to deal with reverse-proxy or not.
     *
     * @return String
     */
    public function getIPAddress() {
        if ($this->isFromTrustedProxy() && isset($_SERVER[self::HEADER_X_FORWARDED_FOR])) {
            return $_SERVER[self::HEADER_X_FORWARDED_FOR];
        } else {
            return $_SERVER[self::HEADER_REMOTE_ADDR];
        }
    }
}
