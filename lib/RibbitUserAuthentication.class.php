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
 * Contains the RibbitUserAuthentication class
 *
 * @package Ribbit
 */
require_once ('RibbitConfig.class.php');
require_once ('RibbitSignedRequest.class.php');
require_once ('RibbitException.class.php');
if (!isset($_SESSION)) {
    session_start();
}
if (isset($_SESSION["ribbit_user_object"])) {
    $u = $_SESSION["ribbit_user_object"];
    RibbitConfig::getInstance()->setUser($u["guid"], $u["login"], $u["token"], $u["secret"]);
}
/**
 * Used to retrieve an oAuth token that allows applications on behalf of a user.
 *
 * @package Ribbit
 * @version 1.3.0
 * @author BT/Ribbit
 */
class RibbitUserAuthentication
{
    /**
     * Normally accessed through Ribbit::getInstance()->Login($email,$password)
     *
     * Throws a RibbitException if an error was returned from the service.
     *
     * Throws an InvalidUserNameOrPasswordException if the credentials are wrong
     *
     * @param string $login The user name that is required to login (required)
     * @param string $password The users password (required)
     * @return boolean if the authentication works ok and tokens are set in session
     */
    static function AuthenticateUser($login, $password)
    {
        unset($_SESSION["ribbit_user_object"]);
        RibbitUserAuthentication::Logoff();
        try {
            $t = RibbitSignedRequest::getInstance()->post(null, "login", $login, $password);
            $t = explode("&", $t);
            $u = explode("=", $t[0]);
            $access_token = $u[1];
            $u = explode("=", $t[1]);
            $access_secret = $u[1];
            $u = explode("=", $t[2]);
            $current_user = $u[1];
            RibbitConfig::getInstance()->setUser($current_user, $login, $access_token, $access_secret);
            $_SESSION["ribbit_user_object"] = array("token" => $access_token, "secret" => $access_secret, "login" => $login, "guid" => $current_user);
        }
        catch(RibbitException $e) {
            RibbitUserAuthentication::Logoff();
            throw new InvalidUserNameOrPasswordException();
        }
        return true;
    }
    /**
     * Logs off the current user and removes details from the PHP session.
     */
    static function Logoff()
    {
        RibbitConfig::getInstance()->setUser(null, null, null, null);
        unset($_SESSION["ribbit_user_object"]);
    }
}
?>