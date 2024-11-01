<?php
require_once "ext/spyc.php";
if (!isset($_SESSION)) {
    session_start();
}
RibbitConfig::getInstance();
/**
 * Holds configuration details. These are all specified in ribbit_config.yml.
 *
 * Application ID, Consumer Key and Secret Key can be changed at runtime. A method is exposed in the Ribbit class to do this easily
 * Http Proxy details are optional. Proxys with automatic configuration scripts (normally javascript files ending in .pac) are not supported.
 */
class RibbitConfig
{
    private $using_session = null;
    /**
     * Normally accessed through Ribbit::getInstance()->getConfig()
     *
     * This method will load the ribbit_config.yml file initialization file only the first time it's called.
     * Subsequent calls will refer to the deserialized  values, some of which a developer can change.
     *
     * @return RibbitConfig The instance of this class
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) $instance = new RibbitConfig();
        return $instance;
    }
    private function RibbitConfig()
    {
        if (!isset($_SESSION["ribbit_config"])) {
            if (!file_exists(dirname(__FILE__) . "/../ribbit_config.yml")) {
                return;
            }
            $_SESSION["ribbit_config"] = spyc::YAMLLoad(dirname(__FILE__) . "/../ribbit_config.yml");
        }
    }
    private function getItem($group, $key)
    {
        $o = null;
        if (isset($_SESSION["ribbit_config"][$group]) && isset($_SESSION["ribbit_config"][$group][$key])) {
            $o = $_SESSION["ribbit_config"][$group][$key];
        }
        return $o;
    }
    private function saveItemInSession($group, $key, $value)
    {
        if (isset($_SESSION)) {
            $_SESSION["ribbit_config"][$group][$key] = $value;
        }
    }
    public function setApplicationCredentials($consumer_key, $secret_key, $application_id, $domain, $account_id)
    {
        if ($consumer_key != $this->getConsumerKey() || $secret_key != $this->getSecretKey()) {
            $this->clearAccessToken();
        }
        $this->setConsumerKey($consumer_key);
        $this->setSecretKey($secret_key);
        $this->setApplicationId($application_id);
        $this->setDomain($domain);
        $this->setAccountId($account_id);
    }
    /**
     * Gets the application id currently being used
     *
     * @return string
     */
    public function getApplicationId()
    {
        return $this->getItem("ribbit", "application_id");
    }
    /**
     * Sets the application id to use
     *
     * @param string $application_id
     */
    public function setApplicationId($application_id)
    {
        $this->saveItemInSession("ribbit", "application_id", $application_id);
    }
    /**
     * Gets the account id currently being used
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->getItem("ribbit", "account_id");
    }
    /**
     * Sets the account id to use
     *
     * @param string $account_id
     */
    public function setAccountId($account_id)
    {
        $this->saveItemInSession("ribbit", "account_id", $account_id);
    }
    /**
     * Gets the domain currently being used
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getItem("ribbit", "domain");
    }
    /**
     * Sets the domain to use
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->saveItemInSession("ribbit", "domain", $domain);
    }
    /**
     * Gets the consumer key currently being used
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->getItem("ribbit", "consumer_key");
    }
    /**
     * Sets the consumer key to use
     *
     * @param string $consumer_key
     */
    public function setConsumerKey($consumer_key)
    {
        $this->saveItemInSession("ribbit", "consumer_key", $consumer_key);
    }
    /**
     * Gets the secret key currently being used
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->getItem("ribbit", "secret_key");
    }
    /**
     * Sets the secret key to use
     *
     * @return
     */
    public function setSecretKey($secret_key)
    {
        $this->saveItemInSession("ribbit", "secret_key", $secret_key);
    }
    /**
     * Set the Access token and secret to authenticate oAuth calls
     *
     * @param string $token the access token to use
     * @param string $token the access secret to use
     * @return void
     */
    public function setAccessToken($token, $secret)
    {
        //$this->_access_token = $token;
        $this->saveItemInSession("ribbit", "access_token", $token);
        //$this->_access_secret = $secret;
        $this->saveItemInSession("ribbit", "access_secret", $secret);
    }
    /**
     * Clear the current acces tokens
     *
     * @return void
     */
    public function clearAccessToken()
    {
        //$this->_access_token = null;
        $this->saveItemInSession("ribbit", "access_token", null);
        //$this->_access_secret = "";
        $this->saveItemInSession("ribbit", "access_secret", null);
        //$this->_user_name = null;
        $this->saveItemInSession("ribbit", "user_name", null);
        //$this->_user_id = null;
        $this->saveItemInSession("ribbit", "user_id", null);
    }
    /**
     * Gets the access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->getItem("ribbit", "access_token");
    }
    /**
     * Gets the user name
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->getItem("ribbit", "user_name");
    }
    /**
     * Gets the user name
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->getItem("ribbit", "user_id");
    }
    /**
     * Change the user access token at runtime. Note that access tokens do expire
     *
     * @param string $user_id an application id
     * @param string $user_name the
     * @param string $access_token a token representing a user session
     * @param string $access_secret a secret key for the user session
     */
    public function setUser($user_id, $user_name, $access_token, $access_secret)
    {
        $this->saveItemInSession("ribbit", "user_id", $user_id);
        $this->saveItemInSession("ribbit", "user_name", $user_name);
        $this->setAccessToken($access_token, $access_secret);
    }
    /**
     * Gets the access token
     *
     * @return string
     */
    public function getAccessSecret()
    {
        return $this->getItem("ribbit", "access_secret");
    }
    /**
     * Gets the ribbit endpoint, defined in ribbit_config.yml
     *
     * @return string
     */
    public function getRibbitEndpoint()
    {
        $e = $this->getItem("ribbit", "endpoint");
        if (substr($e, strlen($e) - 1, 1) != "/") {
            $e = $e . "/";
            $this->setRibbitEndpoint($e);
        }
        return $e;
    }
    /**
     * Sets the ribbit endpoint
     *
     * @return string
     */
    public function setRibbitEndpoint($endpoint)
    {
        $this->saveItemInSession("ribbit", "endpoint", $endpoint);
    }
    /**
     * Gets the http proxy address, defined in ribbit_config.yml
     *
     * @return string
     */
    public function getHttpProxyAddress()
    {
        return $this->getItem("http", "proxy_address");
    }
    /**
     * Gets the username to use with an authenticated http proxy, defined in ribbit_config.yml
     *
     * @return string
     */
    public function getHttpProxyUsername()
    {
        return $this->getItem("http", "proxy_username");
    }
    /**
     * Gets the password to use with an authenticated http proxy, defined in ribbit_config.yml
     *
     * @return string
     */
    public function getHttpProxyPassword()
    {
        return $this->getItem("http", "proxy_password");
    }
    /**
     * Defines whether to log http requests made to Ribbit to the syslog (or event viewer in Windows)
     *
     * @return string
     */
    public function getLog()
    {
        return $this->getItem("ribbit", "log");
    }
}
?>