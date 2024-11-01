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
 * Contains the RibbitMediaFiles class
 *
 * @package Ribbit
 */
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
require_once ('RibbitUtil.class.php');
/**
 * The Media resource represents audio, video, or text files that may be stored and retrieved from shared folders on the Ribbit virtual file system for use in audio or video applications.
 * This service is currently employed by the default voicemail application for Ribbit which deposits audio files in a virtual folder corresponding to the call ID within the media space.
 */
class RibbitMediaFiles
{
    /**
     * Normally accessed through Ribbit::getInstance()->Media()
     *
     * @return RibbitMediaFiles An instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitMediaFiles();
        return $instance;
    }
    private function RibbitMediaFiles()
    {
    }
    /**
     * Creates a new virtual folder
     * This method calls the Ribbit service
     *
     * @param string $id An identifier for this access control list entry (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return string A folder identifier
     */
    public function createFolder($id, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($id)) {
            $exceptions[] = "id is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $vars = array();
        $vars["id"] = $id;
        $uri = "media/" . $domain_value;
        $result = $signed_request->post($vars, $uri);
        return RibbitUtil::get_id_from_uri($result);
    }
    /**
     * Gets the contents of a folder
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @param int $start_index the first result to return when requesting a paged list (optional)
     * @param int $count the number of results to return when requesting a paged list (required if a start index is supplied)
     * @param string $filter_by an key to an index with which to filter results (optional)
     * @param string $filter_value the value to search within the filter for (required if a filter is supplied)
     * @return mixed An associative array, containing paging details and an ordered array, each entry of which contains an associative array containing details about the Media
     */
    public function getFolder($folder, $domain = null, $start_index = null, $count = null, $filter_by = null, $filter_value = null)
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
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $q = array();
        if (!(is_null($start_index) || is_null($count))) {
            $q[] = "startIndex=" . $start_index . "&count=" . $count;
        }
        if (!(is_null($filter_by) || is_null($filter_value))) {
            $q[] = "filterBy=" . $filter_by . "&filterValue=" . $filter_value;
        }
        $q = (count($q) > 0) ? "?" . implode('&', $q) : "";
        $uri = "media/" . $domain_value . "/" . $folder . $q;
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
     * Get the access control list for a folder
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the AccessControlList
     */
    public function getFolderAcl($folder, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/" . $folder . "/acl";
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Updates the access control list for a folder
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string[] $read_users An array of User GUIDS who have permission to read the resource (optional)
     * @param string[] $write_users An array of Users GUIDS who have permission to write to the resource (optional)
     * @param string[] $read_apps An array of Application GUIDS who have permission to read the resource (optional)
     * @param string[] $write_apps An array of Application GUIDS who have permission to write to the resource (optional)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the AccessControlList
     */
    public function updateFolderAcl($folder, $read_users = null, $write_users = null, $read_apps = null, $write_apps = null, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($read_users)) {
            $exceptions[] = "When defined, read_users must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($write_users)) {
            $exceptions[] = "When defined, write_users must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($read_apps)) {
            $exceptions[] = "When defined, read_apps must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($write_apps)) {
            $exceptions[] = "When defined, write_apps must be an array of at least one item";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $vars = array();
        if (isset($read_users)) {
            $vars["readUsers"] = $read_users;
        }
        if (isset($write_users)) {
            $vars["writeUsers"] = $write_users;
        }
        if (isset($read_apps)) {
            $vars["readApps"] = $read_apps;
        }
        if (isset($write_apps)) {
            $vars["writeApps"] = $write_apps;
        }
        $uri = "media/" . $domain_value . "/" . $folder . "/acl";
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Removes a folder, and all it's contents
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return boolean true if successfully removed
     */
    public function removeFolder($folder, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/" . $folder;
        $result = $signed_request->delete($uri);
        return true;
    }
    /**
     * Gets the access control list for a file
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $file The name of a file (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the AccessControlList
     */
    public function getFileAcl($folder, $file, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string($file)) {
            $exceptions[] = "file is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/" . $folder . "/" . $file . "/acl";
        $result = $signed_request->get($uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Updates the access control list for a file
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $file The name of a file (required)
     * @param string[] $read_users An array of User GUIDS who have permission to read the resource (optional)
     * @param string[] $write_users An array of Users GUIDS who have permission to write to the resource (optional)
     * @param string[] $read_apps An array of Application GUIDS who have permission to read the resource (optional)
     * @param string[] $write_apps An array of Application GUIDS who have permission to write to the resource (optional)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return mixed An associative array containing details about the AccessControlList
     */
    public function updateFileAcl($folder, $file, $read_users = null, $write_users = null, $read_apps = null, $write_apps = null, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string($file)) {
            $exceptions[] = "file is required";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($read_users)) {
            $exceptions[] = "When defined, read_users must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($write_users)) {
            $exceptions[] = "When defined, write_users must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($read_apps)) {
            $exceptions[] = "When defined, read_apps must be an array of at least one item";
        }
        if (!RibbitUtil::is_non_empty_array_if_defined($write_apps)) {
            $exceptions[] = "When defined, write_apps must be an array of at least one item";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $vars = array();
        if (isset($read_users)) {
            $vars["readUsers"] = $read_users;
        }
        if (isset($write_users)) {
            $vars["writeUsers"] = $write_users;
        }
        if (isset($read_apps)) {
            $vars["readApps"] = $read_apps;
        }
        if (isset($write_apps)) {
            $vars["writeApps"] = $write_apps;
        }
        $uri = "media/" . $domain_value . "/" . $folder . "/" . $file . "/acl";
        $result = $signed_request->put($vars, $uri);
        $result = json_decode($result, true);
        return $result["entry"];
    }
    /**
     * Removes a file
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $file The name of a file (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return boolean true if successfully removed
     */
    public function removeFile($folder, $file, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string($file)) {
            $exceptions[] = "file is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/" . $folder . "/" . $file;
        $result = $signed_request->delete($uri);
        return true;
    }
    /**
     * Removes all files associated with a call
     * This method calls the Ribbit service
     *
     * @param string $call_id A numeric call identifier (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return boolean true if successfully removed
     */
    public function removeAllMediaForCall($call_id, $domain = null)
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
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/call:" . $call_id;
        $result = $signed_request->delete($uri);
        return true;
    }
    /**
     * Retrieves a file in a folder
     * This method calls the Ribbit service
     *
     * @param string $folder The name of a folder (required)
     * @param string $file The name of a file (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     */
    public function getFileInFolder($open_file_handle, $folder, $file, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        if (Ribbit::getInstance()->getUserId() == null) {
            throw new AuthenticatedUserRequiredException();
        }
        $user_id = Ribbit::getInstance()->getUserId();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($folder)) {
            $exceptions[] = "folder is required";
        }
        if (!RibbitUtil::is_valid_string($file)) {
            $exceptions[] = "file is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (is_null($open_file_handle) || !fstat($open_file_handle)) {
            $exceptions[] = "An open file handle to a writeable file must be supplied";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/" . $folder . "/" . $file;
        $result = $signed_request->getToFile($uri, $open_file_handle, 'audio/mpeg');
        return $result;
    }
    /**
     * Downloads the audio content of a call to a specified file
     * This method calls the Ribbit service
     *
     * @param string $call_id A numeric call identifier (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     */
    public function getMediaForCall($open_file_handle, $call_id, $domain = null)
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
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (is_null($open_file_handle) || !fstat($open_file_handle)) {
            $exceptions[] = "An open file handle to a writeable file must be supplied";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/call:" . $call_id . "/" . $call_id . ".mp3";
        $result = $signed_request->getToFile($uri, $open_file_handle, 'audio/mpeg');
        return $result;
    }
    /**
     * Creates a temporary URL that can be used for streaming files associated with a call
     * This method calls the Ribbit service
     *
     * @param string $call_id A numeric call identifier (required)
     * @param string $domain A domain name, normally the current users (optional, if not supplied, the value from configuration is used)
     * @return string A url that can be used to stream audio
     */
    public function getUrlForMediaForCall($call_id, $domain = null)
    {
        $signed_request = RibbitSignedRequest::getInstance();
        $exceptions = array();
        if (!RibbitUtil::is_valid_string($call_id)) {
            $exceptions[] = "call_id is required";
        }
        if (!RibbitUtil::is_valid_string_if_defined($domain)) {
            $exceptions[] = "When defined, domain must be a string of one or more characters";
        }
        if (count($exceptions) > 0) {
            throw new InvalidArgumentException(implode(";", $exceptions));
        }
        $domain_value = isset($domain) ? $domain : Ribbit::getConfig()->getDomain();
        $uri = "media/" . $domain_value . "/call:" . $call_id . "/" . $call_id . ".mp3";
        $result = $signed_request->getStreamableUrl($uri);
        return $result;
    }
}
