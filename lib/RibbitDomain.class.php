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
 * Contains the RibbitDomain class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * A Domain defines a name space for developers for any applications and users that they create.
 * Any user within a domain is able to login using any application within the same domain.
 * Domains are created automatically for developers using the Developer Portal when applications are created.
 * When new users are created these users are created in the same domain as the application used to create them.
 * Users are only able to GET details of the domain that they are in.
 */
class RibbitDomain
{
    /**
     * Normally accessed through Ribbit::getInstance()->Domains()
     *
     * @return RibbitDomain An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitDomain();
        return $instance;
    }
    private function RibbitDomain()
    {
    }
    /**
     * Gets a Domain
     * This method calls the Ribbit service
     *
     * @param string $name A Domain Name (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the Domain
     */
    public function getDomain($name = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string_if_defined($name)) {
            $exceptions[] = "When defined, name must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $name_value = isset($name) ? $name : Ribbit::getConfig()->getDomain();
        $uri = "domains/" . $name_value;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Gets a collection of Domains
     * This method calls the Ribbit service
     *
     * @param string $filter_by an key to an index with which to filter results (optional)
     * @param string $filter_value the value to search within the filter for (required if a filter is supplied)
     * @return mixed An ordered array, each entry of which contains an associative array containing details about the Domain
     */
    public function getDomains($filter_by = null, $filter_value = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        $filter_param_error = RibbitUtil::check_filter_parameters($filter_by, $filter_value);
        if ($filter_param_error != null) {
            $exceptions[] = $filter_param_error;
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $q = array();
        if (!(is_null($filter_by) || is_null($filter_value))) {
            $q[] = "filterBy=" . $filter_by . "&filterValue=" . $filter_value;
        }
        $q = (count($q) > 0) ? "?" . implode('&', $q) : "";
        $uri = "domains" . $q;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        $result = $result['entry'];
        return $result;
    }
}
