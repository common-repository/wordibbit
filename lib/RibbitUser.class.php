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
 * Contains the RibbitUser class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * A User represents registered end-users of Ribbit applications, and are defined by a unique ID and login.
 * The unique ID, assigned to newly created User resources, also serves as the identifier for containers of other User-centric resources including Calls, Messages, and Devices.
 */
class RibbitUser
{
    /**
     * Normally accessed through Ribbit::getInstance()->Users()
     *
     * @return RibbitUser An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitUser();
        return $instance;
    }
    private function RibbitUser()
    {
    }
    /**
     * Get Users in the current domain
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @param string $filter_by an key to an index with which to filter results (optional)
     * @param string $filter_value the value to search within the filter for (required if a filter is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the User
     */
    public function getUsers($start_index = null, $count = null, $filter_by = null, $filter_value = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        $paging_param_error = RibbitUtil::check_paging_parameters($start_index, $count);
        if ($paging_param_error != null) {
            $exceptions[] = $paging_param_error;
        }
        $filter_param_error = RibbitUtil::check_filter_parameters($filter_by, $filter_value);
        if ($filter_param_error != null) {
            $exceptions[] = $filter_param_error;
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $q = array();
        if (!(is_null($start_index) || is_null($count))) {
            $q[] = "startIndex=" . $start_index . "&count=" . $count;
        }
        if (!(is_null($filter_by) || is_null($filter_value))) {
            $q[] = "filterBy=" . $filter_by . "&filterValue=" . $filter_value;
        }
        $q = (count($q) > 0) ? "?" . implode('&', $q) : "";
        $uri = "users" . $q;
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
     * Get User details
     * This method calls the Ribbit service
     *
     * @param string $user_id Globally unique User identifier (GUID) (required)
     * @return mixed An associative array containing details about the User
     */
    public function getUser($user_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($user_id)) {
            $exceptions[] = "user_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $uri = "users/" . $user_id;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Create a new user
     * This method calls the Ribbit service
     *
     * @param string $login User login (e.g. foo@bar.com), unique within a domain (required)
     * @param string $password A Password for the User. (required)
     * @param string $first_name Non-unique name to refer to User. (optional)
     * @param string $last_name Non-unique name to refer to User. (optional)
     * @param int $account_id The billing account ID used by this user, this must refer to a valid account in order for the user to conduct billable activity such as making calls, requesting purpose numbers etc. The account ID may be updated for a given user if and only if the authorized user making the request is the owner of the billing account or else the account ID is the same as the billing account ID used by the developer that "owns" the application making the request. (optional)
     * @param string $domain The Domain to which the User belongs. (optional)
     * @return string An user identifier
     */
    public function createUser($login, $password, $first_name = null, $last_name = null, $account_id = null, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($login)) {
            $exceptions[] = "login is required";
        }
        if (!RibbitUtil::is_valid_string($password)) {
            $exceptions[] = "password is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($first_name)) {
            $exceptions[] = "When defined, first_name must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($last_name)) {
            $exceptions[] = "When defined, last_name must be a string of one or more characters";
        }
        if (!RibbitUtil::is_positive_integer_if_defined($account_id)) {
            $exceptions[] = "When defined, account_id must be a positive integer";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["login"] = $login;
        $vars["password"] = $password;
        if (isset($first_name)) {
            $vars["firstName"] = $first_name;
        }
        if (isset($last_name)) {
            $vars["lastName"] = $last_name;
        }
        if (isset($account_id)) {
            $vars["accountId"] = $account_id;
        }
        if (isset($domain)) {
            $vars["domain"] = $domain;
        }
        $uri = "users";
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Update a users details, for example, change their billing account or reset their password
     * This method calls the Ribbit service
     *
     * @param string $login User login (e.g. foo@bar.com), unique within a domain (optional)
     * @param string $password A Password for the User. (optional)
     * @param string $first_name Non-unique name to refer to User. (optional)
     * @param string $last_name Non-unique name to refer to User. (optional)
     * @param string $pwd_status Set to 'reset' to have a new password sent to the User's email. (optional)
     * @param int $account_id The billing account ID used by this user, this must refer to a valid account in order for the user to conduct billable activity such as making calls, requesting purpose numbers etc. The account ID may be updated for a given user if and only if the authorized user making the request is the owner of the billing account or else the account ID is the same as the billing account ID used by the developer that "owns" the application making the request. (optional)
     * @param string $domain The Domain to which the User belongs. (optional)
     * @return mixed An associative array containing details about the User
     */
    public function updateUser($login = null, $password = null, $first_name = null, $last_name = null, $pwd_status = null, $account_id = null, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!isset($login) && !isset($password) && !isset($first_name) && !isset($last_name) && !isset($pwd_status) && !isset($account_id) && !isset($domain)) {
            $exceptions[] = "At least one parameter must be supplied";
        }
        if (!RibbitUtil::is_valid_string_if_defined($login)) {
            $exceptions[] = "When defined, login must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($password)) {
            $exceptions[] = "When defined, password must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($first_name)) {
            $exceptions[] = "When defined, first_name must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($last_name)) {
            $exceptions[] = "When defined, last_name must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($pwd_status)) {
            $exceptions[] = "When defined, pwd_status must be a string of one or more characters";
        }
        if (!RibbitUtil::is_positive_integer_if_defined($account_id)) {
            $exceptions[] = "When defined, account_id must be a positive integer";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        if (isset($login)) {
            $vars["login"] = $login;
        }
        if (isset($password)) {
            $vars["password"] = $password;
        }
        if (isset($first_name)) {
            $vars["firstName"] = $first_name;
        }
        if (isset($last_name)) {
            $vars["lastName"] = $last_name;
        }
        if (isset($pwd_status)) {
            $vars["pwdStatus"] = $pwd_status;
        }
        if (isset($account_id)) {
            $vars["accountId"] = $account_id;
        }
        if (isset($domain)) {
            $vars["domain"] = $domain;
        }
        $uri = "users/" . $user_id;
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Requests a password reset for a user. This method is not compatible with 2 legged authentication, where a secret key is NOT supplied
     * This method calls the Ribbit service
     *
     * @param string $user_id Globally unique User identifier (GUID) (required)
     * @return mixed An associative array containing details about the User
     */
    public function requestPasswordReset($user_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($user_id)) {
            $exceptions[] = "user_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["pwdStatus"] = "reset";
        $uri = "users/" . $user_id;
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Gets an array of User details, filtered by the supplied login parameter
     * This method calls the Ribbit service
     *
     * @param string $login User login (e.g. foo@bar.com), unique within a domain (required)
     * @return mixed An ordered array, each entry of which contains an associative array containing details about the User
     */
    public function getUsersFilteredByLogin($login)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($login)) {
            $exceptions[] = "login is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->getUsers(null, null, "login", $login);
        return $result;
    }
}
