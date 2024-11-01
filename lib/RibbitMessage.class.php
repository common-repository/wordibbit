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
 * Contains the RibbitMessage class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * Messages resources represent text, voicemail, SMS, and other forms of media that may be exchanged and saved by Users
 */
class RibbitMessage
{
    /**
     * Normally accessed through Ribbit::getInstance()->Messages()
     *
     * @return RibbitMessage An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitMessage();
        return $instance;
    }
    private function RibbitMessage()
    {
    }
    /**
     * To send an SMS the recipients in the array must be formatted tel:xxnnnnnn where xx is a country code and nnnnnn is their phone number.<br/>When sending a SMS the sender must also be a tel:xxnnnnn uri, and a telephone number registered to the current User on the Ribbit Platform, either an allocated inbound (purpose) number or a cell phone. <br/>The body will be the content that gets displayed on the phone. <br/>The title is sometimes referred to as the message id, and some cellular devices and carriers make this available.
     * This method calls the Ribbit service
     *
     * @param string[] $recipients A list of details about the recipients of the Message (required)
     * @param string $body The body of the Message (optional)
     * @param string $sender The device ID that sent the Message (optional)
     * @param string $title The title of the Message (optional)
     * @return string A message identifier
     */
    public function createMessage($recipients, $body = null, $sender = null, $title = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_non_empty_array($recipients)) {
            $exceptions[] = "recipients is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($body)) {
            $exceptions[] = "When defined, body must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($sender)) {
            $exceptions[] = "When defined, sender must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_string_if_defined($title)) {
            $exceptions[] = "When defined, title must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        $vars["recipients"] = $recipients;
        if (isset($body)) {
            $vars["body"] = $body;
        }
        if (isset($sender)) {
            $vars["sender"] = $sender;
        }
        if (isset($title)) {
            $vars["title"] = $title;
        }
        $uri = "messages/" . $user_id . "/outbox";
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Update a message. Move it to a folder or flag it
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @param string $current_folder A folder that contains messages (optional)
     * @param boolean $new Whether the message is flagged as 'new' (optional)
     * @param boolean $urgent Whether the message is flagged as 'urgent' (optional)
     * @param string $folder A folder that contains messages (optional)
     * @return mixed An associative array containing details about the Message
     */
    public function updateMessage($message_id, $current_folder = null, $new = null, $urgent = null, $folder = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($current_folder)) {
            $exceptions[] = "When defined, current_folder must be a string of one or more characters";
        }
        if (!RibbitUtil::is_valid_bool_if_defined($new)) {
            $exceptions[] = "When defined, new must be boolean";
        }
        if (!RibbitUtil::is_valid_bool_if_defined($urgent)) {
            $exceptions[] = "When defined, urgent must be boolean";
        }
        if (!RibbitUtil::is_valid_string_if_defined($folder)) {
            $exceptions[] = "When defined, folder must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $vars = array();
        if (isset($new)) {
            $vars["new"] = $new;
        }
        if (isset($urgent)) {
            $vars["urgent"] = $urgent;
        }
        if (isset($folder)) {
            $vars["folder"] = $folder;
        }
        $uri = "messages/" . $user_id . "/" . $folder . "/" . $message_id;
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Gets details of a message in a folder
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @param string $folder A folder that contains messages (required)
     * @return mixed An associative array containing details about the Message
     */
    public function getMessage($message_id, $folder)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $uri = "messages/" . $user_id . "/" . $folder . "/" . $message_id;
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Gets a collection of details of messages sent by the current User. This method supports pagination
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Message
     */
    public function getSentMessages($start_index = null, $count = null)
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
        $uri = "messages/" . $user_id . "/sent" . $q;
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
     * Gets a collection of details of messages received by the current User. This method supports pagination
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Message
     */
    public function getReceivedMessages($start_index = null, $count = null)
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
        $uri = "messages/" . $user_id . "/inbox" . $q;
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
     * Gets a collection of details of messages associated with the current User. This method supports pagination and filtering, both seperately and in combination
     * This method calls the Ribbit service
     *
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @param string $filter_by an key to an index with which to filter results (optional)
     * @param string $filter_value the value to search within the filter for (required if a filter is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Message
     */
    public function getMessages($start_index = null, $count = null, $filter_by = null, $filter_value = null)
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
        $uri = "messages/" . $user_id . $q;
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
     * Gets details of a message sent by the current User
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @return mixed An associative array containing details about the Message
     */
    public function getSentMessage($message_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->getMessage($message_id, "sent");
        return $result;
    }
    /**
     * Gets details of a sent message
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @return mixed An associative array containing details about the Message
     */
    public function getReceivedMessage($message_id)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->getMessage($message_id, "inbox");
        return $result;
    }
    /**
     * Flag a message as 'urgent'
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @param string $folder A folder that contains messages (required)
     * @return mixed An associative array containing details about the Message
     */
    public function markMessageUrgent($message_id, $folder = "inbox")
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateMessage($message_id, $folder, null, true, null);
        return $result;
    }
    /**
     * Flag a message as 'new'
     * This method calls the Ribbit service
     *
     * @param string $message_id A message identifier (required)
     * @param string $folder A folder that contains messages (required)
     * @return mixed An associative array containing details about the Message
     */
    public function markMessageNew($message_id, $folder = "inbox")
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($message_id)) {
            $exceptions[] = "message_id is required";
        }
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->updateMessage($message_id, $folder, true, null, null);
        return $result;
    }
    /**
     * Get a list of messages filtered by a tag
     * This method calls the Ribbit service
     *
     * @param string $tag  (required)
     * @return mixed An ordered array, each entry of which contains an associative array containing details about the Message
     */
    public function getMessagesFilteredByTag($tag)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($tag)) {
            $exceptions[] = "tag is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->getMessages(null, null, "tags", $tag);
        return $result;
    }
    /**
     * Get a list of messages filtered by status. Values are 'delivered', 'received' and 'failed'
     * This method calls the Ribbit service
     *
     * @param string $status The value which represents the delivery status, to this recipient, of the Message (required)
     * @return mixed An ordered array, each entry of which contains an associative array containing details about the Message
     */
    public function getMessagesFilteredByStatus($status)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($status)) {
            $exceptions[] = "status is required";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $result = $this->getMessages(null, null, "messageStatus", $status);
        return $result;
    }
}
