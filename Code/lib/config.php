<?php

/**
 * DESCRIPTION:
 *     Configuration class for website url, username, password and cookie information
 */
class Config
{
    var $website;
    var $username;
    var $password;
    var $cookie_directory;
    var $cookie_filename;
    var $cookie_file;

    /**
     * DESCRIPTION:
     *     Set data members (default constructor)
     *
     * IN:
     *     $file    path to configuration file, defaults to "config.xml"
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     */
    function __construct($file = "config.xml")
    {
        // load configuration file
        try {
            $config = new SimpleXmlElement(file_get_contents($file));
        } catch (Exception $e) {
            die("Error: failed to open configuration file." . "<br />");
        }

        // set data members
        $node =  $config->xpath("/config/neogolism/website");
        $this->website = (string) $node[0];
        $node =  $config->xpath("/config/neogolism/username");
        $this->username = (string) $node[0];
        $node =  $config->xpath("/config/neogolism/password");
        $this->password = (string) $node[0];
        $node =  $config->xpath("/config/neogolism/cookie/directory");
        $this->cookie_directory = (string) $node[0];
        $node =  $config->xpath("/config/neogolism/cookie/filename");
        $this->cookie_filename = (string) $node[0];

        // set path to cookie file
        $this->cookie_file = $this->cookie_directory . DIRECTORY_SEPARATOR . $this->cookie_filename;
    }

    /**
     * DESCRIPTION:
     *     Check configuration file errors
     *
     * IN:
     *     $username    username
     *     $password    password
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     $return_array['STATUS']        = SUCCESS if success, else ERROR
     *     $return_array['ERROR_MESSAGE'] = error message (concatenated string)
     *
     * TODO:
     *     check valid website url
     */
    function check()
    {
        $error_message = null;

        // website
        if (empty($this->website)) {
            $error_message .= "website is not specified in the configuration file";
        }

        // username
        if (empty($this->username)) {
            $delimiter = (is_null($error_message)) ? "" : ", ";    // for string concatenation
            $error_message .= $delimiter . "username is not specified in the configuration file";
        }

        // password
        if (empty($this->password)) {
            $delimiter = (is_null($error_message)) ? "" : ", ";    // for string concatenation
            $error_message .= $delimiter . "password is not specified in the configuration file";
        }

        // cookie directory with write/execute permission
        $delimiter = (is_null($error_message)) ? "" : ", ";    // for string concatenation
        if (empty($this->cookie_directory)) {
            $error_message .= $delimiter . "cookie directory is not specified in the configuration file";
        } else if (!is_writable($this->cookie_directory)) {
            $error_message .= $delimiter . "cookie directory '$this->cookie_directory' is not writable";
        } else if (!is_executable($this->cookie_directory)) {
            $error_message .= $delimiter . "cookie directory '$this->cookie_directory' is not executable";
        }

        // cookie filename
        if (empty($this->cookie_filename)) {
            $delimiter = (is_null($error_message)) ? "" : ", ";    // for string concatenation
            $error_message .= $delimiter . "cookie filename is not specified in the configuration file";
        }

        // set return array
        $return_array = array();
        if (is_null($error_message)) {
            $return_array['STATUS'] = SUCCESS;
            $return_array['ERROR_MESSAGE'] = null;
        } else {
            $error_message .= ".";    // trailing delimiter
            $return_array['STATUS'] = ERROR;
            $return_array['ERROR_MESSAGE'] = $error_message;
        }

        // return results
        return $return_array;
    }
}

?>
