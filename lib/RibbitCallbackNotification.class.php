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
 * Contains the RibbitCallbackNotification class
 *
 * @package Ribbit
 */
require_once ('RibbitUtil.class.php');
/**
 * Serializes and exposes a callback notification
 *
 * The Ribbit Platform may send notifications of various events back to your web server.<br/>
 * In order to receive these you must ensure that you have set a Notification URL for your application,
 * which you can do by
 * <pre>
 * $ribbit = Ribbit::getInstance();
 * //find out what domain your app is in, if you don't know
 * $app_id = $ribbit->getCurrentApplicationId();
 * $domain = Ribbit::Domains()->getDomain();
 * $url = "http://www.yourserver.com/notificationpage.php"
 * $ribbit->Applications()->updateApplication($app_id, $domain, $url);
 * </pre>
 *
 * Then in notificationpage.php you would include the following code
 * <pre>
 * require_once "path/to/Ribbit.php"
 *
 * $notification = RibbitCallbackNotification::getInstance();
 * echo "the affected resource was " . $notification->getResource();
 * echo "there are " . count( $notification->getParameters() ) . " parameters in this callback
 * </pre>
 *
 * @package Ribbit
 * @version 1.5.2.5
 * @author BT/Ribbit
 */
class RibbitCallbackNotification
{
    private $_time;
    private $_resource;
    private $_params;
    private $_type;
    /**
     * Gets an instance of RibbitCallbackNotification and parses the details POSTed from the Ribbit Platfrom
     *
     * Throws a RibbitException if there is no parseable callback.
     *
     * @return RibbitCallbackNotification
     */
    public static function getInstance()
    {
        $out = null;
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            if (strtolower($_SERVER['CONTENT_TYPE']) == 'application/json') {
                $body = @file_get_contents('php://input');
                $out = new RibbitCallbackNotification($body);
                return $out;
            }
        }
        throw new RibbitException("Could not parse the Ribbit Callback Notification", "500");
    }
    public function RibbitCallbackNotification($body)
    {
        $parsed = $this->parse($body);
        if (!$parsed) {
            throw new RibbitException("Could not parse the Ribbit Callback Notification", "400");
        }
    }
    /**
     * Returns the time that the event occured in format "yyyy-MM-DDTHH:nn:ssZ"
     *
     *  @return string the time the event occured
     */
    function getTime()
    {
        return $this->_time;
    }
    /**
     * Returns the type of the event that occured
     *
     *  @return string the type of event
     */
    function getType()
    {
        return $this->_type;
    }
    /**
     * Returns the resource affected by the event
     *
     *  @return string the resource affected by the event
     */
    function getResource()
    {
        return $this->_resource;
    }
    /**
     * Returns an associative array of parameters describing the event
     *
     *  @return array an associative array of parameters describing the event
     */
    function getParameters()
    {
        return $this->_params;
    }
    /**
     * Returns the value of a given parameter associated with the event
     *
     * @param string $param the name of the parameter being queried
     *  @return string a value or an empty string if $param is not in the event
     */
    function getParameter($param)
    {
        return isset($this->_params[$param]) ? $this->_params[$param] : '';
    }
    private function parse($o)
    {
        $o = json_decode($o, true);
        if (!isset($o['time']) || !isset($o['resource']) || !RibbitUtil::is_assoc_array($o['params']) || !isset($o['type'])) {
            return false;
        } else {
            $this->_time = $o['time'];
            $this->_resource = $o['resource'];
            $this->_params = $o['params'];
            $this->_type = $o['type'];
            return true;
        }
    }
}
?>
