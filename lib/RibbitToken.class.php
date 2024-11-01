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
 * Contains the RibbitToken class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * A Token is a resource that allows authentication of a User.
 * Token-based authentication allows you to build Applications and deploy them for multiple Users. An unlimited number of Users are able to interact in guest mode with Token-authenticated Ribbit applications.
 */
class RibbitToken
{
    /**
     * Normally accessed through Ribbit::getInstance()->Tokens()
     *
     * @return RibbitToken An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitToken();
        return $instance;
    }
    private function RibbitToken()
    {
    }
    /**
     * Create a new Token. It is possible to specify the number of concurrent callers, and limit the token to operate only between certain dates.
     * This method calls the Ribbit service
     *
     * @param string $type The type of token ('uphone' for YouPhone Tokens) (required)
     * @param string $callee A Device URI that represents the number or address called (eg tel:xxnnnnnnnn) (required)
     * @param string $caller A Device URI that represents the number or address used as caller ID (eg tel:xxnnnnnnnn) (required)
     * @param string $description A textual description of the Token (required)
     * @param date $start_date The date before which the Token is invalid (optional)
     * @param date $end_date The date after which the token is invalid (optional)
     * @param int $max_concurrent The maximum number of concurrent connections using this token (optional)
     * @return string A token identifier
     */
    public function createToken($type, $callee, $caller, $description, $start_date = null, $end_date = null, $max_concurrent = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($type)) {
            $exceptions[] = "type is required";
        }
        if (!RibbitUtil::is_valid_string($callee)) {
            $exceptions[] = "callee is required";
        }
        if (!RibbitUtil::is_valid_string($caller)) {
            $exceptions[] = "caller is required";
        }
        if (!RibbitUtil::is_valid_string($description)) {
            $exceptions[] = "description is required";
        }
        if (!RibbitUtil::is_date_if_defined($start_date)) {
            $exceptions[] = "start_date is not a valid date";
        }
        if (!RibbitUtil::is_date_if_defined($end_date)) {
            $exceptions[] = "end_date is not a valid date";
        }
        if (!RibbitUtil::is_positive_integer_if_defined($max_concurrent)) {
            $exceptions[] = "When defined, max_concurrent must be a positive integer";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["type"] = $type;
        $vars["callee"] = $callee;
        $vars["caller"] = $caller;
        $vars["description"] = $description;
        if (isset($start_date)) {
            $vars["startDate"] = RibbitUtil::format_date($start_date);
        }
        if (isset($end_date)) {
            $vars["endDate"] = RibbitUtil::format_date($end_date);
        }
        if (isset($max_concurrent)) {
            $vars["maxConcurrent"] = $max_concurrent;
        }
        $uri = "tokens";
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Retrieve the details of a Token that belongs to the current User
     * This method calls the Ribbit service
     *
     * @param string $token_id A Token identifier (required)
     * @return mixed An associative array containing details about the Token
     */
    public function getToken($token_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($token_id)) {
            $exceptions[] = "token_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $uri = "tokens/" . $user_id . "/" . $token_id;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Retrieve a list of details about Tokens that belong to the current User. This method supports pagination
     * This method calls the Ribbit service
     *
     * @return mixed An ordered array, each entry of which contains an associative array containing details about the Token
     */
    public function getTokens()
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $uri = "tokens/" . $user_id;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        $result = $result['entry'];
        return $result;
    }
    /**
     * Remove a Token that belongs to the current User
     * This method calls the Ribbit service
     *
     * @param string $token_id A Token identifier (required)
     * @return boolean true if successfully removed
     */
    public function removeToken($token_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($token_id)) {
            $exceptions[] = "token_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $uri = "tokens/" . $user_id . "/" . $token_id;
        $result = $signed_request->delete($uri);
        return true;
    }
    /**
     * Creates a new YouPhone Token
     * This method calls the Ribbit service
     *
     * @param string $callee A Device URI that represents the number or address called (eg tel:xxnnnnnnnn) (required)
     * @param string $caller A Device URI that represents the number or address used as caller ID (eg tel:xxnnnnnnnn) (required)
     * @param string $description A textual description of the Token (required)
     * @param date $start_date The date before which the Token is invalid (optional)
     * @param date $end_date The date after which the token is invalid (optional)
     * @param int $max_concurrent The maximum number of concurrent connections using this token (optional)
     * @return string A Token identifier
     */
    public function createYouPhoneToken($callee, $caller, $description, $start_date = null, $end_date = null, $max_concurrent = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($callee)) {
            $exceptions[] = "callee is required";
        }
        if (!RibbitUtil::is_valid_string($caller)) {
            $exceptions[] = "caller is required";
        }
        if (!RibbitUtil::is_valid_string($description)) {
            $exceptions[] = "description is required";
        }
        if (!RibbitUtil::is_date_if_defined($start_date)) {
            $exceptions[] = "start_date is not a valid date";
        }
        if (!RibbitUtil::is_date_if_defined($end_date)) {
            $exceptions[] = "end_date is not a valid date";
        }
        if (!RibbitUtil::is_positive_integer_if_defined($max_concurrent)) {
            $exceptions[] = "When defined, max_concurrent must be a positive integer";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->createToken("uphone", $callee, $caller, $description, $start_date, $end_date, $max_concurrent);
        return $result;
    }
}
