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
 * Contains the RibbitCall class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * Calls are telephony events between Devices from the point of view of a given User. Calls are initiated by a POST to a User's Call collection, with parameters to represent the source and destination numbers.
 * Note: Phone numbers must have 'tel:' before the phone number.
 */
class RibbitCall
{
    /**
     * Normally accessed through Ribbit::getInstance()->Calls()
     *
     * @return RibbitCall An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitCall();
        return $instance;
    }
    private function RibbitCall()
    {
    }
    /**
     * Calls may be made between any two Devices. To connect Calls to PSTN numbers on the production platform, credit must be available in the User's Account to cover the cost of connecting for at least one minute.
     * This method calls the Ribbit service
     *
     * @param string $source Device ID (or alias) from which the Call is made (SIP: or TEL: only) (required)
     * @param string[] $dest Device IDs to which this Call is made (SIP: or TEL: only) (required)
     * @return string A call identifier
     */
    public function createThirdPartyCall($source, $dest)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($source)) {
            $exceptions[] = "source is required";
        }
        if (!RibbitUtil::is_non_empty_array($dest)) {
            $exceptions[] = "dest is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["source"] = $source;
        $vars["dest"] = $dest;
        $uri = "calls/" . $user_id;
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Calls may be made to one or more Devices. To connect Calls to PSTN numbers on the production platform, credit must be available in the User's Account to cover the cost of connecting for at least one minute.
     * This method calls the Ribbit service
     *
     * @param string[] $legs Device IDs which participate in this call (SIP: or TEL: only) (required)
     * @param string $callerid The number which will be presented when devices are called (optional)
     * @param string $mode The mode of a call or leg describes it's state.  Options are: hold, mute, hangup, talk (optional)
     * @return string A call identifier
     */
    public function createCall($legs, $callerid = null, $mode = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_non_empty_array($legs)) {
            $exceptions[] = "legs is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($callerid)) {
            $exceptions[] = "When defined, callerid must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($mode)) {
            $exceptions[] = "When defined, mode must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["legs"] = $legs;
        if (isset($callerid)) {
            $vars["callerid"] = $callerid;
        }
        if (isset($mode)) {
            $vars["mode"] = $mode;
        }
        $uri = "calls/" . $user_id;
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Once a Call is made the details may be retrieved to show the current status of each Leg. Only the Call owner is able to query the Call details.
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return mixed An associative array containing details about the Call
     */
    public function getCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $uri = "calls/" . $user_id . "/" . $call_id;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * The Call history can be retrieved by making a GET on the Call resource.  The result is a collection of Calls.
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Call
     */
    public function getCalls($start_index = null, $count = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
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
        $uri = "calls/" . $user_id . $q;
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
     * Updates a call to change the mode of all legs
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $id Unique numeric Call identifier (optional)
     * @param string $mode The mode of a call or leg describes it's state.  Options are: hold, mute, hangup, talk (optional)
     * @param boolean $active Whether the call is active (optional)
     * @return boolean true if the method succeeds
     */
    public function updateCall($call_id, $id = null, $mode = null, $active = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($id)) {
            $exceptions[] = "When defined, id must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($mode)) {
            $exceptions[] = "When defined, mode must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_bool_if_defined($active)) {
            $exceptions[] = "When defined, active must be boolean";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        if (isset($id)) {
            $vars["id"] = $id;
        }
        if (isset($mode)) {
            $vars["mode"] = $mode;
        }
        if (isset($active)) {
            $vars["active"] = $active;
        }
        $uri = "calls/" . $user_id . "/" . $call_id;
        $result = $signed_request->put($vars, $uri);
        return true;
    }
    /**
     * Updates the mode of a call leg
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @param string $mode The mode of a call or leg describes it's state.  Options are: hold, mute, hangup, talk (required)
     * @return boolean true if the method succeeds
     */
    public function updateCallLeg($call_id, $leg_id, $mode)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (!RibbitUtil::is_valid_string($mode)) {
            $exceptions[] = "mode is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["mode"] = $mode;
        $uri = "calls/" . $user_id . "/" . $call_id . "/" . $leg_id;
        $result = $signed_request->put($vars, $uri);
        return true;
    }
    /**
     * Transfers a call leg from one call to another
     * This method calls the Ribbit service
     *
     * @param string $source_call_id The call id from which the leg should be transferred (required)
     * @param string $source_leg_id The source call leg identifier (required)
     * @param string $destination_call_id The call id to which the leg should be transferred (required)
     * @return boolean true if the method succeeds
     */
    public function transferLeg($source_call_id, $source_leg_id, $destination_call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($source_call_id)) {
            $exceptions[] = "source_call_id is required";
        }
        if (!RibbitUtil::is_valid_string($source_leg_id)) {
            $exceptions[] = "source_leg_id is required";
        }
        if (!RibbitUtil::is_valid_string($destination_call_id)) {
            $exceptions[] = "destination_call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($destination_call_id, $source_call_id . "/" . $source_leg_id, null, null);
        return $result;
    }
    /**
     * Mutes a call leg
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @return boolean true if the method succeeds
     */
    public function muteLeg($call_id, $leg_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCallLeg($call_id, $leg_id, "mute");
        return $result;
    }
    /**
     * Takes a call leg off mute
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @return boolean true if the method succeeds
     */
    public function unmuteLeg($call_id, $leg_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCallLeg($call_id, $leg_id, "talk");
        return $result;
    }
    /**
     * Puts a call leg on hold
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @return boolean true if the method succeeds
     */
    public function holdLeg($call_id, $leg_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCallLeg($call_id, $leg_id, "hold");
        return $result;
    }
    /**
     * Takes a call leg off hold
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @return boolean true if the method succeeds
     */
    public function unholdLeg($call_id, $leg_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCallLeg($call_id, $leg_id, "talk");
        return $result;
    }
    /**
     * Removes a leg from a call
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @param string $leg_id The call leg identifier (required)
     * @return boolean true if the method succeeds
     */
    public function hangupLeg($call_id, $leg_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string($leg_id)) {
            $exceptions[] = "leg_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCallLeg($call_id, $leg_id, "hangup");
        return $result;
    }
    /**
     * Mute all legs on a call
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return boolean true if the method succeeds
     */
    public function muteCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($call_id, null, "mute", null);
        return $result;
    }
    /**
     * Take all muted legs on a call off mute
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return boolean true if the method succeeds
     */
    public function unmuteCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($call_id, null, "talk", null);
        return $result;
    }
    /**
     * Puts all legs on a call on hold
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return boolean true if the method succeeds
     */
    public function holdCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($call_id, null, "hold", null);
        return $result;
    }
    /**
     * Takes all held legs on a call off hold
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return boolean true if the method succeeds
     */
    public function unholdCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($call_id, null, "hold", null);
        return $result;
    }
    /**
     * Terminates the call
     * This method calls the Ribbit service
     *
     * @param string $call_id Unique numeric Call identifier (required)
     * @return boolean true if the method succeeds
     */
    public function hangupCall($call_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateCall($call_id, null, null, false);
        return $result;
    }
}
