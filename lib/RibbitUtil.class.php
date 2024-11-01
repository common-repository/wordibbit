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
 * Contains utility functions used in the Ribbit PHP client Library
 *
 * @package Ribbit
 */
/**
 * The utility class for the Ribbit PHP Client Library
 *
 * @package Ribbit
 * @version 1.5.2.5
 * @author BT/Ribbit
 */
class RibbitUtil
{
    /**
     * Gets the identifier of the resource from the uri
     *
     * @param $uri string the full path of the resource
     * @return string the identifier of the resource
     */
    public static function get_id_from_uri($uri)
    {
        $i = strrpos($uri, "/");
        return trim(substr($uri, $i + 1, strlen($uri) - $i));
    }
    public static function get_long_from_uri($uri)
    {
        return 0 + RibbitUtil::get_id_from_uri($uri);
    }
    /**
     * Gets the inbound number from the id  (the id should be returned by create device)
     *
     * @param string $id the id of the resource
     * @return string the inbound number
     */
    public static function get_inbound_number_from_id($id)
    {
        $i = strrpos($id, ":");
        return str_replace("+", "", trim(substr($id, $i + 1, strlen($id) - $i)));
    }
    //	/**
    //	 * Converts an object to an associative array, removing null values.
    //	 *
    //	 * @param $obj an object
    //	 * @return array the object as an array.
    //	 */
    //	public static function object_to_array($obj){
    //		if(is_array($obj) || is_object($obj)){
    //	    	$result = array();
    //	    	foreach($obj as $key => $value){
    //	    		if ($value != null){
    //	      			$result[$key] = RibbitUtil::object_to_array($value);
    //	    		}
    //	    	}
    //	    	return $result;
    //	  }
    //	  return $obj;
    //	}
    
    /**
     * Formats a date suitable into a string suitable for the Ribbit Service
     *
     * @param $d string a date
     * @return string the formatted date
     */
    public static function format_date($d)
    {
        return RibbitUtil::format_date_for_requests($d) . "Z";
    }
    /**
     * Formats a date suitable into a string suitable for the Ribbit Service
     *
     * @param $d string a date
     * @return string the formatted date
     */
    public static function format_date_for_requests($d)
    {
        $t = date_parse($d);
        return (string)$t["year"] . "-" . (($t["month"] < 10) ? "0" . (string)$t["month"] : (string)$t["month"]) . "-" . (($t["day"] < 10) ? "0" . (string)$t["day"] : (string)$t["day"]) . "T" . (($t["hour"] < 10) ? "0" . (string)$t["hour"] : (string)$t["hour"]) . ":" . (($t["minute"] < 10) ? "0" . (string)$t["minute"] : (string)$t["minute"]) . ":" . (($t["second"] < 10) ? "0" . (string)$t["second"] : (string)$t["second"]);
    }
    /**
     * Detects whether a value is an associative array
     *
     * @param $var mixed any value
     * @return bool true if $var is an associative array, otherwise false.
     *
     */
    public static function is_assoc_array($var)
    {
        if (!is_array($var) || empty($var)) {
            return false;
        }
        foreach(array_keys($var) as $k => $v) {
            if ($k !== $v) {
                return true;
            }
        }
        return false;
    }
    /**
     * Returns an array of error messages for paging parameters.
     *
     * @param $start_index int the start index
     * @param $count int the count
     * @return array an array for error messages
     */
    public static function check_paging_parameters($start_index = null, $count = null)
    {
        $exceptions = array();
        if (!is_null($start_index) && is_null($count)) {
            $exceptions[] = "If start_index is specified, count must be specified too";
        }
        if (!is_null($count) && is_null($start_index)) {
            $exceptions[] = "If count is specified, start_index must be specified too";
        }
        if (!is_null($start_index) && !is_null($count)) {
            if (!RibbitUtil::is_positive_integer($start_index)) {
                $exceptions[] = "start_index must be a positive integer";
            }
            if (!RibbitUtil::is_positive_integer($count)) {
                $exceptions[] = "count must be a positive integer";
            }
        }
        return (count($exceptions) == 0) ? null : implode(";", $exceptions);
    }
    /**
     * Returns an array of error messages for paging parameters.
     *
     * @param $start_index int the start index
     * @param $count int the count
     * @return array an array for error messages
     */
    public static function check_filter_parameters($filter_by = null, $filter_value = null)
    {
        $exceptions = array();
        if (!is_null($filter_by) && is_null($filter_value)) {
            $exceptions[] = "If filter_by is specified, filter_value must be specified too";
        }
        if (!is_null($filter_value) && is_null($filter_by)) {
            $exceptions[] = "If filter_value is specified, filter_by must be specified too";
        }
        if (!is_null($filter_by) && !is_null($filter_value)) {
            if (!RibbitUtil::is_valid_string_if_defined($filter_by)) {
                $exceptions[] = "When defined, filter_by must be a valid filtering property of the resource";
            }
            if (!RibbitUtil::is_valid_string_if_defined($filter_value)) {
                $exceptions[] = "When defined, filter_value  must be a string of one or more characters";
            }
        }
        return (count($exceptions) == 0) ? null : implode(";", $exceptions);
    }
    /**
     * Checks if the supplied value is a positive integer.
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is a positive integer.
     */
    public static function is_positive_integer($v)
    {
        return is_int($v) && $v >= 0;
    }
    /**
     * Checks if the supplied value is a positive integer.
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is a positive integer.
     */
    public static function is_positive_integer_if_defined($v)
    {
        return is_null($v) || empty($v) || RibbitUtil::is_positive_integer($v);
    }
    /**
     * Checks if the supplied value is a valid notification url.
     *
     * @param $url string the value to check.
     * @return bool true if the value is a valid notification url.
     */
    public static function is_valid_notification_url($url)
    {
        $url = substr($url, -1) == "/" ? substr($url, 0, -1) : $url;
        if (!$url || $url == "") return false;
        if (!($parts = @parse_url($url))) return false;
        else {
            if ($parts[scheme] != "http" && $parts[scheme] != "https") return false;
            else if (!eregi("^[0-9a-z]([-.]?[0-9a-z])*.[a-z]{2,4}$", $parts[host], $regs)) return false;
            else if (!eregi("^([0-9a-z-]|[_])*$", $parts[user], $regs)) return false;
            else if (!eregi("^([0-9a-z-]|[_])*$", $parts[pass], $regs)) return false;
            else if (!eregi("^[0-9a-z/_.@~-]*$", $parts[path], $regs)) return false;
            else if (!eregi("^[0-9a-z?&=#,]*$", $parts[query], $regs)) return false;
        }
        return true;
    }
    /**
     * Checks if the supplied value is an array of at least one item
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is an array of at least one item
     */
    public static function is_non_empty_array($v)
    {
        return is_array($v) && count($v) > 0;
    }
    /**
     * Checks if the supplied value is an array of at least one item, if it has been set.
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is an array of at least one item
     */
    public static function is_non_empty_array_if_defined($v)
    {
        return !isset($v) || RibbitUtil::is_non_empty_array($v);
    }
    public static function is_valid_string($v)
    {
        return is_string($v) && strlen($v) > 0;
    }
    /**
     * Checks if the supplied value is a string of at least one character in length
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is a string of at least one character in length
     */
    public static function is_valid_string_if_defined($v)
    {
        return !isset($v) || RibbitUtil::is_valid_string($v);
    }
    public static function is_valid_double($v)
    {
        return is_double($v);
    }
    public static function is_valid_double_if_defined($v)
    {
        return !isset($v) || is_valid_double($v);
    }
    /**
     * Checks if the supplied value is a bool
     *
     * @param $v mixed the value to check.
     * @return bool true if the value is a bool
     */
    public static function is_valid_bool_if_defined($v)
    {
        return !isset($v) || is_bool($v);
    }
    public static function is_date_if_defined($v)
    {
        return is_null($v) || !isset($v) || strtotime($v);
    }
    public static function is_long_if_defined($v)
    {
        return empty($v) || is_long($v);
    }
    public static function is_valid_base64_data($v)
    {
        return preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $v);
    }
}
?>