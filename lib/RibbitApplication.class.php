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
 * Contains the RibbitApplication class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * An Application represents web or desktop clients that can use Ribbit APIs, and which expose Ribbit services to end Users.
 * Developers can only access certain details of applications that they have created, such as secret keys, using the Developer Portal.
 * Applications are primarily used to define security credentials for consumer keys and secret keys as required to sign messages to the service.
 * Applications can also be configured to receive event notifications via HTTP posts from the Ribbit platform to an application specific URL.
 */
class RibbitApplication
{
    /**
     * Normally accessed through Ribbit::getInstance()->Applications()
     *
     * @return RibbitApplication An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitApplication();
        return $instance;
    }
    private function RibbitApplication()
    {
    }
    /**
     * Get application details
     * This method calls the Ribbit service
     *
     * @param string $domain The domain to which the Application belongs (optional, if not supplied, the value from configuration is used)
     * @param string $application_id Globally unique Application identifier. (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the Application
     */
    public function getApplication($domain = null, $application_id = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($application_id)) {
            $exceptions[] = "When defined, application_id must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $application_id_value = isset($application_id) ? $application_id : Ribbit::getConfig()->getApplicationId();
        $uri = "apps/" . $domain_value . ":" . $application_id_value;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Retrieves details of applications in the same domain as the current application
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Application
     */
    public function getApplications($start_index = null, $count = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        $paging_param_error = RibbitUtil::check_paging_parameters($start_index, $count);
        if ($paging_param_error != null) {
            $exceptions[] = $paging_param_error;
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $q = array();
        if (!(is_null($start_index) || is_null($count))) {
            $q[] = "startIndex=" . $start_index . "&count=" . $count;
        }
        $q = (count($q) > 0) ? "?" . implode('&', $q) : "";
        $uri = "apps" . $q;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        if (!is_null($start_index)) {
            if (!isset($result["totalResults"])) {
                $result["totalResults"] = 0;
                $result["itemsPerPage"] = 0;
                $result["startIndex"] = 0;
            }
        } else {
            $result = $result['entry'];
        }
        return $result;
    }
    /**
     * Changes the URL used for event callbacks, can also toggle whether the application supports two legged (desktop) authentication
     * This method calls the Ribbit service
     *
     * @param string $notification_url The URL where event notifications are sent.  (optional)
     * @param boolean $allow2legged Whether this Application can use two legged (desktop) authentication (optional)
     * @param string $domain The domain to which the Application belongs (optional, if not supplied, the value from configuration is used)
     * @param string $application_id Globally unique Application identifier. (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the Application
     */
    public function updateApplication($notification_url = null, $allow2legged = null, $domain = null, $application_id = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!isset($notification_url) && !isset($allow2legged) && !isset($domain) && !isset($application_id)) {
            $exceptions[] = "At least one parameter must be supplied";
        }
        if (!RibbitUtil::is_valid_string_if_defined($notification_url)) {
            $exceptions[] = "When defined, notification_url must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_bool_if_defined($allow2legged)) {
            $exceptions[] = "When defined, allow2legged must be boolean";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($application_id)) {
            $exceptions[] = "When defined, application_id must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $application_id_value = isset($application_id) ? $application_id : Ribbit::getConfig()->getApplicationId();
        $vars = array();
        if (isset($notification_url)) {
            $vars["notificationUrl"] = $notification_url;
        }
        if (isset($allow2legged)) {
            $vars["allow2legged"] = $allow2legged;
        }
        $uri = "apps/" . $domain_value . ":" . $application_id_value;
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
}
