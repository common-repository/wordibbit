<?php
// Copyright (c) 2009, Ribbit / BT Group PLC
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without modification,
// are permitted provided that the following conditions are met:
//
// * Redistributions of source code must retain the above copyright notice,
//   this list of conditions and the following disclaimer.
// * Redistributions in binary form must reproduce the above copyright notice, this
//	 list of conditions and the following disclaimer in the documentation and/or other
//	 materials provided with the distribution.
// * Neither the name of BT Group PLC nor the names of its contributors may be used
//	 to endorse or promote products derived from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
// EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
// OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
// SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
// SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT
// OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
// TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
// EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

/**
 * Contains the RibbitSignedRequest class
 *
 * @package Ribbit
 */
require_once 'RibbitConfig.class.php';
require_once (dirname(__FILE__) . '/../Ribbit.php');
require_once ('RibbitException.class.php');
/**
 *YOU SHOULD NOT USE THIS CLASS DIRECTLY. Used to make signed GET and POST requests, respecting the OAuth specification, to the Ribbit REST platform.
 *
 * @package Ribbit
 * @version 1.5.2.5
 * @author BT/Ribbit
 */
class RibbitSignedRequest
{
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const POST = 'POST';
    const GET = 'GET';
    private $_returnedHeaders;
    private $_http_status;
    private $_last_response;
    private $_location;
    private $timeout = 10;
    protected $_headers = array();
    private $ch;
    private function RibbitSignedRequest()
    {
    }
    /**
     * Normally accessed through another method
     *
     * @return RibbitSignedRequest The instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new RibbitSignedRequest();
        }
        return $instance;
    }
    private function getConfig()
    {
        return RibbitConfig::getInstance();
    }
    /**
     * Returns the last HTTP status
     *
     * @return string an HTTP status code
     */
    public function getLastHTTPStatus()
    {
        return $this->_http_status;
    }
    /**
     * Returns the last HTTP response
     *
     * @return string normally JSON data, but could be text
     */
    public function getLastHTTPResponse()
    {
        return $this->_last_response;
    }
    /**
     * If the last method called had a Location header, it is returned here
     *
     * @return string a URI
     */
    public function getLocation()
    {
        return $this->_location;
    }
    /**
     * Makes an HTTP GET request
     *
     * @param string $uri the web resource to GET (required)
     *
     * @return string the response from the GET request
     */
    /**
     * Makes a signed HTTP GET request
     *
     * @param string $uri the web resource to GET (required)
     *
     * @return string the response from the GET request
     */
    public function get($uri)
    {
        return $this->send($uri, RibbitSignedRequest::GET);
    }
    /**
     * Makes a signed HTTP DELETE request
     *
     * @param string $uri the web resource to DELETE (required)
     *
     * @return void
     */
    public function delete($uri)
    {
        return $this->send($uri, RibbitSignedRequest::DELETE);
    }
    /**
     * Makes a signed HTTP POST request
     *
     * @param string $uri the web resource to POST to (required)
     * @param array $vars an associative array of values to post (optional)
     *
     * @return string normally the location of a created resource
     */
    public function post($vars, $uri, $x_auth_username = null, $x_auth_password = null)
    {
        return $this->send($uri, RibbitSignedRequest::POST, $vars, null, "*", $x_auth_username, $x_auth_password);
    }
    /**
     * Makes a signed HTTP POST request
     *
     * @param string $uri the web resource to POST to (required)
     * @param array $vars an associative array of values to post (optional)
     *
     * @return string normally the location of a created resource
     */
    public function postFile($base64_data, $uri)
    {
        return $this->send($uri, RibbitSignedRequest::POST, null, null, "*", null, null, $base64_data);
    }
    /**
     * Makes a signed HTTP PUT request
     *
     * @param string $uri the web resource to update (required)
     * @param array $vars an associative array of values to post (optional)
     *
     * @return void
     */
    public function put($vars, $uri)
    {
        return $this->send($uri, RibbitSignedRequest::PUT, $vars, null, null, null, null);
    }
    /**
     * Makes a signed HTTP GET request, and pipes the output to the file handle supplied
     *
     * @param string $uri the web resource to update (required)
     * @param pointer $open_file_handle an open file handle to a writeable file, to pipe the output to
     *
     * throws InvalidArgumentException if a file handle is not supplied, or if it is not possible to fstat the file
     *
     * @return true if the method was successfully called, and the file was written to
     *
     */
    public function getToFile($uri, $open_file_handle, $accept_type = "application/json")
    {
        return $this->send($uri, RibbitSignedRequest::GET, null, $open_file_handle, $accept_type);
    }
    public function getStreamableUrl($uri)
    {
        if (substr($uri, 0, 4) != "http") $uri = $this->getConfig()->getRibbitEndpoint() . $uri;
        $header = $this->create_headers($uri, 'GET', null, 'audio/mpeg', null, null);
        $s = "";
        foreach($header as $h) {
            $s.= (($s != "") ? "|" : "") . $h;
        }
        $s = $uri . "?h=" . rawurlencode($s);
        return $s;
    }
    /**
     * Logs a message using the PHP syslog command, if logging is enabled in ribbit_config.php
     *
     *
     * @param int $level the error level
     * @param string $message the message to log
     *
     * @return void
     */
    private function log($level, $message)
    {
        switch ($this->getConfig()->getLog()) {
        case (true):
        case ("true"):
        case (1):
        case ("1"):
        case (-1):
        case ("-1"):
            openlog("Ribbit", LOG_PID, LOG_USER);
            syslog($level, $message);
            closelog();
            break;

        default:
        }
    }
    private function create_auth_header($uri, $method, $body, $x_auth_username, $x_auth_password)
    {
        $body_sig = "";
        $body_sig_header = "";
        if (!empty($body)) {
            $body_sig = $this->sign_for_oauth($body);
            $body_sig_header = ", xoauth_body_signature_method=\"HMAC-SHA1\", xoauth_body_signature=\"" . rawurlencode($body_sig) . "\"";
        }
        $nonce = $this->generate_nonce();
        $ts = $this->current_millis();
        $normalized_url = $this->normalizeUrl($uri);
        $qps = @parse_url($uri);
        if (isset($qps["query"])) {
            $qps = $qps["query"];
        } else {
            $qps = "";
        }
        $qps = (strlen($qps) > 0) ? explode("&", $qps) : array();
        $q = array();
        $q['oauth_consumer_key'] = $this->getConfig()->getConsumerKey();
        $q['oauth_nonce'] = $nonce;
        $q['oauth_signature_method'] = 'HMAC-SHA1';
        $q['oauth_timestamp'] = $ts;
        if (!is_null($this->getConfig()->getAccessToken())) {
            $q['oauth_token'] = $this->getConfig()->getAccessToken();
        }
        if (!is_null($x_auth_password)) {
            $q['x_auth_password'] = $x_auth_password;
        }
        if (!is_null($x_auth_username)) {
            $q['x_auth_username'] = $x_auth_username;
        }
        if ($body_sig != "") {
            $q['xoauth_body_signature'] = $body_sig;
            $q['xoauth_body_signature_method'] = 'HMAC-SHA1';
        }
        for ($i = 0; $i < count($qps); $i++) {
            $qp = explode('=', $qps[$i]);
            $q[$qp[0]] = $qp[1];
        }
        ksort($q);
        $x = '';
        foreach($q as $k => $v) {
            $x.= ((strlen($x) > 0) ? "&" : "") . $k . "=" . $v;
        }
        $string_to_sign = $method . '&' . rawurlencode($normalized_url) . '&' . rawurlencode($x);
        $string_sig = $this->sign_for_oauth($string_to_sign);
        $auth_header = "OAuth realm=\"http://oauth.ribbit.com\"" . ", oauth_consumer_key=\"" . $this->getConfig()->getConsumerKey() . "\"" . ", oauth_nonce=\"" . $nonce . "\"" . ", oauth_signature_method=\"HMAC-SHA1\"" . ", oauth_timestamp=\"" . $ts . "\"" . ", oauth_signature=\"" . rawurlencode($string_sig) . "\"" . ((!is_null($this->getConfig()->getAccessToken())) ? ",oauth_token=\"" . $this->getConfig()->getAccessToken() . "\"" : "") . ((!is_null($x_auth_password)) ? ",x_auth_password=\"" . $x_auth_password . "\"" : "") . ((!is_null($x_auth_username)) ? ",x_auth_username=\"" . $x_auth_username . "\"" : "") . $body_sig_header;
        return $auth_header;
    }
    private function create_headers($uri, $method, $body, $accept_type, $x_auth_username, $x_auth_password)
    {
        $header = array();
        if (!empty($body)) $header[] = "Content-type: application/json";
        $header[] = "Accept: " . $accept_type;
        $header[] = "Authorization: " . $this->create_auth_header($uri, $method, $body, $x_auth_username, $x_auth_password);
        $header[] = "User-Agent: ribbit_php_library_" . Ribbit::VERSION;
        if (!empty($body)) {
            $header[] = "Content-length: " . strlen($body) . "\r\n";
            $header[] = $body;
        }
        return $header;
    }
    private function send($uri, $method, $vars = null, $fh = null, $accept_type = "application/json", $x_auth_username = null, $x_auth_password = null, $base64_data = null)
    {
        //sometimes we'll get passed a full location
        if (substr($uri, 0, 4) != "http") $uri = $this->getConfig()->getRibbitEndpoint() . $uri;
        $hasBody = $method == RibbitSignedRequest::POST || $method == RibbitSignedRequest::PUT;
        $body = null;
        if ($hasBody && $base64_data == null) {
            $body = ($vars == "") ? "" : json_encode($vars);
        }
        $header = $this->create_headers($uri, $method, $body, $accept_type, $x_auth_username, $x_auth_password);
        $this->log(LOG_INFO, sprintf("Signed Request %s to %s\nHeaders: %s", $method, $uri, implode($header, "\n")));
        if (!empty($base64_data)) {
            $body = $base64_data;
        }
        $ch = curl_init();
        $this->headers = array();
        $this->_returnedHeaders = '';
        $this->_http_status = '';
        $this->_location = '';
        $this->_returnedHeaders = array();
        $proxy_address = $this->getConfig()->getHttpProxyAddress();
        if (!empty($proxy_address)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy_address);
            $proxy_username = $this->getConfig()->getHttpProxyUsername();
            $proxy_password = $this->getConfig()->getHttpProxyPassword();
            if (!empty($proxy_username) && !empty($proxy_password)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_username . ":" . $proxy_password);
            }
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'readHeader'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if (is_null($fh)) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $out = curl_exec($ch);
        } else {
            $out = false;
            curl_setopt($ch, CURLOPT_FILE, $fh);
            curl_exec($ch);
            $out = true;
        }
        curl_close($ch);
        $this->_last_response = $out;
        $errorString = $this->translateStatus($uri, $out);
        if (!is_null($errorString)) {
            $this->log(LOG_WARNING, sprintf("Response from %s\nHeaders: %s\n%s", $uri, implode($this->headers, ""), $out));
            if ($this->_http_status == "409") {
                throw new ResourceAlreadyExistsException($this->_http_status);
            }
            if ($this->_http_status == "404") {
                throw new ResourceNotFoundException($uri, $this->_http_status);
            }
            if ($this->_http_status == "402") {
                throw new InsufficientCreditException();
            }
            throw new RibbitException($errorString, $this->_http_status);
        }
        $this->log(LOG_INFO, sprintf("Response from %s\nHeaders: %s\n%s", $uri, implode($this->headers, ""), $out));
        return ($this->_location != '') ? $this->_location : $out;
    }
    private function sign_for_oauth($clear_text)
    {
        return base64_encode(hash_hmac('sha1', $clear_text, $this->getConfig()->getSecretKey() . '&' . $this->getConfig()->getAccessSecret(), true));
    }
    private function readHeader($ch, $string)
    {
        if (trim($string) == '') return strlen($string);
        $this->headers[] = $string;
        $h = explode(':', $string, 2);
        if (stristr($h[0], 'HTTP')) {
            $s = explode(' ', $string);
            $this->_http_status = $s[1];
        } else if ($h[0] == 'Location') {
            $this->_location = trim(str_replace('Location: ', '', $string));
        } else {
            $this->_returnedHeaders[$h[0]] = $h[1];
        }
        return strlen($string);
    }
    public function normalizeUrl($url, $include_query = false, $include_fragment = false, $default_scheme = 'http://')
    {
        $url_components = @parse_url($url);
        if ($url_components === false) return false;
        $default_url_components = array('scheme' => '', 'host' => '', 'path' => '', 'query' => '', 'fragment' => '', 'port' => '');
        $url_components = array_merge($default_url_components, $url_components);
        if (($url_components['scheme'] == '') OR ($url_components['host'] == '')) {
            if ($url_components['path'] == '' AND $url_components['query'] == '') return false;
            $url = $default_scheme . $url;
            if ($new_url_components = @parse_url($url)) {
                $url_components = array_merge($default_url_components, $new_url_components);
            }
        }
        $url_components['path'] = (empty($url_components['path']) OR $url_components['path'] == '/') ? '/' : rtrim($url_components['path'], '/');
        $url_components['query'] = ($include_query) ? $url_components['query'] : false;
        $url_components['fragment'] = ($include_fragment) ? $url_components['fragment'] : false;
        //if the default ports for the scheme have been set, UNSET them.
        if ($url_components['scheme'] == 'http' && $url_components['port'] == '80') $url_components['port'] = '';
        if ($url_components['scheme'] == 'https' && $url_components['port'] == '443') $url_components['port'] = '';
        $url = $this->unparse($url_components['scheme'], strtolower($url_components['host']), $url_components['port'], $url_components['path'], $url_components['query'], $url_components['fragment']);
        return $url;
    }
    private function unparse($scheme, $host, $port = false, $path = '/', $query = false, $fragment = false)
    {
        if (!$scheme) $scheme = 'http';
        if (!$host) return false;
        $result = $scheme . '://' . $host;
        if ($port) $result.= ':' . $port;
        if (!$path) $path = '';
        $result.= $path;
        if ($query) $result.= '?' . $query;
        if ($fragment) $result.= '#' . $fragment;
        return $result;
    }
    private function generate_nonce()
    {
        return $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . "-" . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . "-" . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . "-" . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . "-" . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char() . $this->random_char();
    }
    private function random_char()
    {
        $possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
        return $char;
    }
    private function current_millis()
    {
        list($usec, $sec) = explode(" ", microtime());
        return round(((float)$usec + (float)$sec) * 1000);
    }
    /**
     * A helper function that translates an HTTP error status to something seen in RibbitException
     *
     * @param string $uri an arbitrary string, but usually the URI that was used in the request that caused the error
     *
     * @return string an error message
     */
    protected function translateStatus($uri, $out)
    {
        if (strpos($out, "purpose numbers") > 0) {
            $this->_http_status = "500";
        }
        $error_string = null;
        if (substr($this->_http_status, 0, 1) == "5") {
            if ($out) {
                $error_string = $out;
            } else {
                $error_string = $uri . " was not found";
            }
        } else {
            switch ($this->_http_status) {
            case "400":
                $error_string = $out != null ? $out : "The request was malformed";
                break;

            case "401":
                $error_string = "The request was not authorized";
                break;

            case "402":
                $error_string = "The account has insufficient credit";
                break;

            case "403":
                $error_string = "The request was forbidden";
                break;

            case "404":
                $error_string = $uri . " was not found";
                break;

            case "406":
                $error_string = "The request was not acceptable";
                break;

            case "407":
                $error_string = "Proxy credentials must be specified or were incorrect";
                break;

            case "408":
                $error_string = "The request timed out";
                break;

            case "409":
                $error_string = "A conflict occurred";
                break;
            }
        }
        return $error_string;
    }
}
?>