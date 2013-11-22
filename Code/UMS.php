<?php

/**
 * URI Management Service (UMS)
 *
 * DESCRIPTION:
 *     This API provides web services to create/delete/search URIs with the
 *     web-based RDF Schema vocabulary editor and publishing system Neol-
 *     ogism. The services are designed to accept HTTP POST requests.
 *
 *     The idea of this implementation is to simulate the browser's behavior
 *     rather than deeps into the source code of the Neologism. The steps
 *     to use the services (create/delete/search operations) is first logg-
 *     ing into the Neologism to get a cookie stored, and then goes depen-
 *     ding on its operation.
 *
 *     This code uses XML-formatted configuration file to make it flexi-
 *     ble. The context are as follows:
 *
 *       | <?xml version="1.0" encoding="UTF-8"?>
 *       | <config>
 *       |     <neogolism>
 *       |         <website>NEOLOGISM WEBSITE</website>
 *       |         <username>USERNAME</username>
 *       |         <password>PASSWORD</password>
 *       |         <cookie>
 *       |             <directory>COOKIE DIRECTORY</directory>
 *       |             <filename>COOKIE FILENAME</filename>
 *       |         </cookie>
 *       |     </neogolism>
 *       | </config>
 *
 * REQUIREMENT:
 *     cURL module for PHP (ex. php5-curl)
 *
 * ENVIRONMENT:
 *     PHP version 5
 *     Neologism version 0.5.2
 *
 * @author     Cheng-Wei Yu (OldYu) <cwyu@iis.sinica.edu.tw>
 * @copyright  Open Source
 * @project    OpenISDM (http://openisdm.iis.sinica.edu.tw/)
 *
 * TODO:
 *     1. Support search functionality, and make other input format
 *     2. Support other input format to create/delete/search operation
 */

// define named constants
define("CONFIG_FILE", "./config.xml");    // configuration file location
define("SUCCESS", 1);    // status for operation
define("FAILURE", 2);    // status for operation
define("ERROR", 3);    // status for other errors besides operation
define("OPERATION_CREATE", "create");    // operation create
define("OPERATION_DELETE", "delete");    // operation delete
define("OPERATION_SEARCH", "search");    // operation search

// include libraries
require_once("./lib/config.php");

/**
 * DESCRIPTION:
 *     Virtual main function (code execution starts here)
 *
 * IN:
 *     NONE
 *
 * OUT:
 *     NONE
 * 
 * RETURNS:
 *     NONE
 */
/*
function main()
{
*/
    // load configuration file and create UMS object
    $ums = new UMS(new Config(CONFIG_FILE));

    // login into Neologism to get a cookie stored
    $return_array = $ums->login($ums->config->website, $ums->config->username, $ums->config->password);

    // accept HTTP request or not
    if ($return_array['STATUS'] == ERROR) {
        // response error
        $ums->responseError($return_array['ERROR_MESSAGE']);
        exit;
    } else {
        // start service
        $ums->startService();
    }

    // program terminated
    exit;
/*
}
*/

/**
 * DESCRIPTION:
 *     UMS class
 */
class UMS
{
    var $config;
    var $response_array = array();

    /**
     * DESCRIPTION:
     *     Set data members and check requirements (default constructor)
     *
     * IN:
     *     $config    configuration class
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     */
    function __construct($config)
    {
        // set data members
        $this->config = $config;
        $this->response_array = array();

        // check environment requirement
        if (!function_exists('curl_init')) {
            // response and exit
            responseError("cURL module for PHP not exists.");
            exit;
        }

        // include libraries
        require_once("./lib/config.php");

        // check configuration file correctness
        $return_array = $this->config->check();

        // if error occurs
        if ($return_array['STATUS'] == ERROR) {
            // response and exit
            $this->responseError($return_array['ERROR_MESSAGE']);
            exit;
        }

        // overwrite cookie path defined in open source library
        define("COOKIE_FILE", $config->cookie_file);
        require_once("./lib/open-source/LIB_http.php");
    }

    /**
     * DESCRIPTION:
     *     Response error (forwarding function)
     *
     * IN:
     *     $error_message    error message
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     */
    function responseError($error_message)
    {
        // set 1st parameter special to identify in target function
        $operation = null;

        // set 2nd parameter
        $response_array = array();
        $response_array['STATUS'] = ERROR;
        $response_array['ERROR_MESSAGE'] = $error_message;

        // call target function
        $this->responseReoprtByJSON($operation, $response_array);
    }

    /**
     * DESCRIPTION:
     *     Response information report by JSON format
     *
     * IN:
     *     $operation         operation (create/delete/search)
     *     $response_array    response information
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     *
     * TODO:
     *     Make non-class function for using everywhere, currently somewhere 
     *    (except here) can only use echo() or die() function for response
     */
    function responseReoprtByJSON($operation, $response_array)
    {
        $json_array = array();

        // prepare response information in JSON array
        if (count($response_array) > 0) {
            // make JSON skeleton
            $json_array["responses"] = array();

            // set operation
            $json_array["responses"]["operation"] = $operation;

            // make JSON skeleton
            $json_array["responses"]["success"] = array();    // for operation success
            $json_array["responses"]["failure"] = array();    // for operation failure
            $json_array["responses"]["error"] = array();    // for other errors, ex. configuration error etc.

            // compose response information
            if (is_null($operation) && $response_array['STATUS'] == ERROR) {
                // set metadata
                $tmp_array = array();
                $tmp_array["message"] = $response_array["ERROR_MESSAGE"];

                // append new entity
                array_push($json_array["responses"]["error"], $tmp_array);
            } else {
                // set all operation
                foreach ($response_array as $record_array) {
                    // operation categorize
                    if ($record_array['STATUS'] == SUCCESS) {    // SUCCESS case
                        // set metadata
                        $tmp_array = array();
                        $tmp_array["namespace"] = $record_array["NAMESPACE"];
                        $tmp_array["term"] = $record_array["TERM"];
                        $tmp_array["uri"] = $record_array["URI"];

                        // append new entity
                        array_push($json_array["responses"]["success"], $tmp_array);
                    } else {    // FAILURE case
                        // set metadata
                        $tmp_array = array();
                        $tmp_array["namespace"] = $record_array["NAMESPACE"];
                        $tmp_array["term"] = $record_array["TERM"];
                        $tmp_array["message"] = $record_array["FAILURE_MESSAGE"];

                        // append new entity
                        array_push($json_array["responses"]["failure"], $tmp_array);
                    }
                }
            }
        } else {
            // TODO: error message
            exit;
        }

        // convert JSON information from array to object for response
        $json_object = json_encode($json_array);

        // check converting error
        if (json_last_error() != JSON_ERROR_NONE) {
            // TODO: error message
            exit;
        }

        // response JSON object
        echo $json_object;
    }

    /**
     * DESCRIPTION:
     *     Login into Neologism by simulating browser behavior
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
     *     $return_array['ERROR_MESSAGE'] = error message
     * 
     * TODO:
     *     Make the login url assignment flexible (currently hard-coded)
     */
    function login($homepage, $username, $password)
    {
        // login url
        $login_url = $homepage . "?q=user";

        // set required parameters (simulate browser behavior)
        $ref = "";
        $method = "POST";
        $data_array['name'] = $username;
        $data_array['pass'] = $password;
        $data_array['form_id'] = "user_login";

        // summit data (to get a cookie stored)
        $response = http($login_url, $ref, $method, $data_array, EXCL_HEAD);

        // fetch error message from website (Neologism) if any
        $html = $response['FILE'];
        $class_name = "messages error";    // http://view-source beforehand
        $error_message = $this->getHtmlErrorMessageByClassName($html, $class_name);

        // set return array
        $return_array = array();
        if (is_null($error_message)) {
            $return_array['STATUS'] = SUCCESS;
            $return_array['ERROR_MESSAGE'] = null;
        } else {
            $return_array['STATUS'] = ERROR;
            $return_array['ERROR_MESSAGE'] = $error_message;
        }

        // return results
        return $return_array;
    }

    /**
     * DESCRIPTION:
     *     Create namespace on Neologism by simulating browser behavior
     *
     * IN:
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     $return_array['NAMESPACE']       = namespace
     *     $return_array['STATUS']          = SUCCESS if success, else FAILURE
     *     $return_array['FAILURE_MESSAGE'] = operation failure message
     * 
     * TODO:
     *     Make the add namespace url assignment flexible (currently hard-coded)
     */
    function createNamespace($homepage, $namespace)
    {
        // init return array
        $return_array = array();
        $return_array['NAMESPACE'] = $namespace;
        $return_array['STATUS'] = null;
        $return_array['FAILURE_MESSAGE'] = null;

        // add namespace url
        $add_namespace_url = $homepage . "node/add/neo-vocabulary";

        // set required parameters (simulate browser behavior)
        $ref = "";
        $method = "GET";
        $data_array = null;

        // summit data (to get a required token)
        $response = http($add_namespace_url, $ref, $method, $data_array, EXCL_HEAD);

        // get the required token (http://view-source beforehand for the magic numbers)
        $token = explode("\n",$response['FILE']);
        $token = explode("value=\"",$token[132]);
        $token = explode("\"",$token[1]);
        $token = $token[0];

        // set required parameters (simulate browser behavior)
        $method = "POST";
        $data_array['prefix'] = $namespace;    // required 'Vocabulary ID' field in Neologism
        $data_array['title'] = $namespace;    // required 'Title' field in Neologism
        $data_array['form_token'] = $token;
        $data_array['form_id'] = "neo_vocabulary_node_form";
        $data_array['authors%5B%5D'] = "1";
        $data_array['status'] = "1";    // make it Published
        $data_array['promote'] = "1";

        // summit data (to create namespace)
        $response = http($add_namespace_url, $ref, $method, $data_array, EXCL_HEAD);

        // fetch error message from website (Neologism) if any
        $html = $response['FILE'];
        $class_name = "messages error";    // http://view-source beforehand
        $failure_message = $this->getHtmlErrorMessageByClassName($html, $class_name);

        // set return array
        if (!is_null($failure_message)) {
            $return_array['STATUS'] = FAILURE;
            $return_array['FAILURE_MESSAGE'] = $failure_message;
        } else {
            $return_array['STATUS'] = SUCCESS;
        }

        // return results
        return $return_array;
    }

    /**
     * DESCRIPTION:
     *     Delete namespaces (CSV format) on Neologism by simulating browser behavior
     *     Note: all terms under the namespace will be all deleted
     *
     * IN:
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     $return_array['NAMESPACE']       = namespace
     *     $return_array['STATUS']          = SUCCESS if success, else FAILURE
     *     $return_array['FAILURE_MESSAGE'] = operation failure message
     */
    function deleteNamespace($homepage, $namespace)
    {
        // init return array
        $return_array = array();
        $return_array['NAMESPACE'] = $namespace;
        $return_array['STATUS'] = null;
        $return_array['FAILURE_MESSAGE'] = null;

        // delete namespace url
        $delete_namespace_url = $this->getDeleteNamespaceURL($homepage, $namespace);
        if (empty($delete_namespace_url) == true) {
            // TODO: response error message
            return false;
        }

        // set required parameters (simulate browser behavior)
        $ref = "";
        $method = "GET";
        $data_array = null;

        // summit data (to get a required token)
        $response = http($delete_namespace_url, $ref, $method, $data_array, EXCL_HEAD);

        // get the required token (http://view-source beforehand for the magic numbers)
        $token = explode("\n",$response['FILE']);
        $token = explode("value=\"",$token[53]);
        $token = explode("\"",$token[1]);
        $token = $token[0]; // this is exactly what we want

        // set required parameters (simulate browser behavior)
        $method = "POST";
        $data_array['confirm'] = "1";
        $data_array['op'] = "Delete";
        $data_array['form_token'] = $token;
        $data_array['form_id'] = "node_delete_confirm";

        // summit data (to delete namespace)
        $response = http($delete_namespace_url, $ref, $method, $data_array, EXCL_HEAD);

        // fetch error message from website (Neologism) if any
        $html = $response['FILE'];
        $class_name = "messages error";    // http://view-source beforehand
        $failure_message = $this->getHtmlErrorMessageByClassName($html, $class_name);

        // set return array
        if (!is_null($failure_message)) {
            $return_array['STATUS'] = FAILURE;
            $return_array['FAILURE_MESSAGE'] = $failure_message;
        } else {
            $return_array['STATUS'] = SUCCESS;
        }

        // return results
        return $return_array;
    }

    /**
     * DESCRIPTION:
     *     Create URI on Neologism by simulating browser behavior.
     *     Set both required fields ('Property URI' and 'Label') 
     *     of Neologism to the name of the term.
     *
     * IN:
     *     $homepage     homepage
     *     $namespace    namespace
     *     $term         term
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     $return_array['NAMESPACE']       = namespace
     *     $return_array['TERM']            = term
     *     $return_array['STATUS']          = SUCCESS if success, else FAILURE
     *     $return_array['FAILURE_MESSAGE'] = operation failure message
     *     $return_array['URI']             = URI
     * 
     * TODO:
     *     Check if URI already exist before creating, because Neologism can create the same URI without errors.
     *     The check is a little difficult due to the #hash in URI would be ingored, ex. <http://website/resource#fake> can still be visited.
     */
    function createURI($homepage, $namespace, $term)
    {
        // init return array
        $return_array = array();
        $return_array['NAMESPACE'] = $namespace;
        $return_array['TERM'] = $term;
        $return_array['STATUS'] = null;
        $return_array['FAILURE_MESSAGE'] = null;
        $return_array['URI'] = null;

        // TODO
        // check if URI already exist (Neologism will create the same URI without errors)

        // add term url
        $add_term_url = $this->getAddTermURL($homepage, $namespace);
        if (empty($add_term_url)) {
            // response and exit
            $this->responseError("add term URL not exist.");
            exit;
        }

        // set required parameters (simulate browser behavior)
        $ref = "";
        $method = "GET";
        $data_array = null;

        // summit data (to get a required token)
        $response = http($add_term_url, $ref, $method, $data_array, EXCL_HEAD);

        // get the required token (http://view-source beforehand for the magic numbers)
        $token = explode("\n",$response['FILE']);
        $token = explode("value=\"",$token[130]);
        $token = explode("\"",$token[1]);
        $token = $token[0]; // this is exactly what we want

        // set required parameters (simulate browser behavior)
        $ref = "";
        $method = "POST";
        $data_array['title'] = $term;    // required 'Property URI' field in Neologism
        $data_array['field_label%5B0%5D%5Bvalue%5D'] = $term;    // required 'Label' field in Neologism
        $data_array['form_token'] = $token;
        $data_array['form_id'] = "neo_property_node_form";

        // summit data (to create URI)
        $response = http($add_term_url, $ref, $method, $data_array, EXCL_HEAD);

        // fetch error message from website (Neologism) if any
        $html = $response['FILE'];
        $class_name = "messages error";    // http://view-source beforehand
        $failure_message = $this->getHtmlErrorMessageByClassName($html, $class_name);

        // TODO: skip 2nd condition when Neologism reinstall to normal
        // set return array
        $warning_message = "user warning";
        if (!is_null($failure_message) && substr_count($failure_message, $warning_message) == 0) {
            $return_array['STATUS'] = FAILURE;
            $return_array['FAILURE_MESSAGE'] = $failure_message;
            //$return_array['URI'] = null;
        } else {
            $return_array['STATUS'] = SUCCESS;
            $return_array['URI'] = $homepage . $namespace . "#" . $term;
        }

        // return results
        return $return_array;
    }

    /**
     * DESCRIPTION:
     *     Servcie of control path
     *
     * IN:
     *     NONE
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     * 
     * TODO:
     *     Support other operations
     */
    function startService()
    {
        // control unit
        if (isset($_REQUEST['operation']) && !empty($_REQUEST['operation'])) {
            $operation = $_REQUEST['operation'];
            if ($operation == OPERATION_CREATE) {
                if (isset($_REQUEST['terms']) && !empty($_REQUEST['terms'])) {
                    if (isset($_REQUEST['namespace']) && !empty($_REQUEST['namespace'])) {    // POST_EXAMPLE://service.com?operation=create&namespace=NAMESPACE1&terms=TERM1,TERM2
                        $namespace = $_REQUEST['namespace'];
                        $terms = $_REQUEST['terms'];
                        $this->serviceByCsvTerms($operation, $namespace, $terms);
                    } else {    // defaults to "openisdm" namespace. POST_EXAMPLE://service.com?operation=create&namespace=openisdm&terms=TERM1,TERM2
                        $namespace = "openisdm";    // for fool-proofing and convience
                        $terms = $_REQUEST['terms'];
                        $this->serviceByCsvTerms($operation, $namespace, $terms);
                    }
                } else if (isset($_REQUEST['json_url']) && !empty($_REQUEST['json_url'])) {
                    $json_url = $_REQUEST['json_url'];
                    $this->serviceByJsonURL($operation, $json_url);
                } else {
                    // response error
                    $this->responseError("operation not supported yet.");
                }
            } else if ($operation == OPERATION_DELETE) {    // delete multiple namespaces, all the terms (URIs) under the namespaces will be all deleted
                if (isset($_REQUEST['namespaces']) && !empty($_REQUEST['namespaces'])) {
                    // POST_EXAMPLE://service.com?operation=delete&namespace=NAMESPACE1,NAMESPACE2
                    $namespaces = $_REQUEST['namespaces'];    // CSV format
                    $this->serviceByCsvNamespaces($operation, $namespaces);
                } else {
                    // response error
                    $this->responseError("operation not supported yet.");
                }
            } else if ($operation == OPERATION_SEARCH) {
                // TODO
                $this->responseError("TODO operation.");
            } else {
                // response error
                $this->responseError("operation not supported yet.");
            }
        } else {
            // response error
            $this->responseError("operation not supported yet.");
        }
    }

    /**
     * DESCRIPTION:
     *     Check if namespace exists
     *
     * IN:
     *     NONE
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     Boolean type: true if exists, else false
     */
    function isNamespaceExist($homepage, $namespace)
    {
        $namespace_url = $homepage . $namespace;

        if ($this->isPageURLExist($namespace_url) == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * DESCRIPTION:
     *     Check if namespace exists
     *
     * IN:
     *     NONE
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     Boolean type: true if exists, else false
     * 
     * TODO:
     *     Readable check method
     */
    function isPageURLExist($url) {
        $headers = get_headers($url);
        $code = substr($headers[0], $start = 9, $length = 3);

        // TODO: change code -> http_response_code($bad_request = 400);
        $OK = 200;    // HTTP response code for "HTTP/1.1 200 OK"
        if ($code == $OK) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * DESCRIPTION:
     *     Get error message from HTML by class name
     *
     * IN:
     *     $html          HTML context
     *     $class_name    class name in HTML
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     Returns error massage string if exists, else null
     */
    function getHtmlErrorMessageByClassName($html, $class_name)
    {
        // parse HTML
        $doc = new DOMDocument();
        @$doc->loadHTML($html);    // symbol '@' is to ignore warning message due to the original HTML code
        $xpath = new DomXpath($doc);
        $query_string = "//*[@class=\"$class_name\"]";    // http://view-source beforehand
        $html_tag = $xpath->query($query_string)->item(0);

        // get error message
        $error_message = (is_null($html_tag)) ? null : $html_tag->textContent;

        return $error_message;
    }

    /**
     * DESCRIPTION:
     *     Operation create by JSON URL
     *
     * IN:
     *     $json_url    JSON URL (structure-content describes in design document)
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     */
    function operationCreateByJsonURL($json_url)
    {
        // load JSON
        $json_string = file_get_contents($json_url);
        $json_array = json_decode($json_string, true);

        // check error
        if (json_last_error() != JSON_ERROR_NONE) {
            // response and exit
            $this->responseError("invalid JSON file.");
            exit;
        }

        // parse JSON
        foreach ($json_array as $value) {
            // set namespace and terms
            $namespace = $value['namespace'];
            $terms = $value['terms'];

            // check input format
            if (is_null($namespace) || is_null($terms)) {
                // response and exit
                responseError("invalid input format.");
                exit;
            }

            // variables
            $return_array = null;
            $namespace_error_flag = null;    // to keep any error message until response

            // check if namespace already exist
            if ($this->isNamespaceExist($this->config->website, $namespace) == false) {
                // add namespace
                $return_array = $this->createNamespace($this->config->website, $namespace);

                // set flag
                if ($return_array['STATUS'] == FAILURE) {
                    $namespace_error_flag = true;
                } else {
                    $namespace_error_flag = false;
                }
            }

            // create URIs
            foreach ($terms as $term) {
                // check previous error
                if ($namespace_error_flag == true) {
                    // add response information
                    $return_array['TERM'] = $term;
                } else {
                    // create URI (response will give details whether the URI exists or not)
                    $return_array = $this->createURI($this->config->website, $namespace, $term);
                }

                // append information for response
                array_push($this->response_array, $return_array);
            }
        }

        // report
        $this->responseReoprtByJSON(OPERATION_CREATE, $this->response_array);
    }

    /**
     * DESCRIPTION:
     *     Operation delete by CSV list. Delete multiple namespaces, and 
     *     all the terms (URIs) under the namespaces will be all deleted
     *
     * IN:
     *     $namespaces    a CSV list of comma-separated values
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     */
    function operationDeleteByCsvNamespaces($namespaces)
    {
        // set delimiters and load CSV
        $delimiters = ", ";    // CSV format (comma-separated)
        $tok = strtok($namespaces, $delimiters);

        // parse
        while ($tok != false) {
            $namespace = $tok;

            // delete namespace
            $return_array = $this->deleteNamespace($this->config->website, $namespace);

            // add response information
            $return_array['NAMESPACE'] = $namespace;

            // reverse logic to share with 'responseReoprtByJSON()' function
            if ($return_array['STATUS'] == FAILURE) {
                $return_array['STATUS'] = SUCCESS;
                $uri = $this->config->website . $namespace;
                $return_array['URI'] = $uri;
            } else {
                $return_array['FAILURE_MESSAGE'] = "$namespace namespace is not exists.";
            }

            // append information for response
            array_push($this->response_array, $return_array);

            // get next token
            $tok = strtok(", ");
        }

        // report
        $this->responseReoprtByJSON(OPERATION_DELETE, $this->response_array);
    }

    /**
     * DESCRIPTION:
     *     Operation delete by CSV list. Delete multiple namespaces, and 
     *     all the terms (URIs) under the namespaces will be all deleted
     *
     * IN:
     *     $namespace   namespace
     *     $terms       a CSV list of comma-separated values
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     * 
     * TODO:
     *     Support other operations
     */
    function operationCreateByCsvTerms($namespace, $terms)
    {
        // variables
        $return_array = null;
        $namespace_error_flag = null;    // to keep any error message until response

        // check if namespace already exist
        if ($this->isNamespaceExist($this->config->website, $namespace) == false) {
            // add namespace
            $return_array = $this->createNamespace($this->config->website, $namespace);

            // set flag
            if ($return_array['STATUS'] == FAILURE) {
                $namespace_error_flag = true;
            } else {
                $namespace_error_flag = false;
            }

        }

        // set delimiters and load CSV
        $delimiters = ", ";    // CSV format (comma-separated)
        $term = strtok($terms, $delimiters);

        // parse and create URIs
        while ($term != false) {
            // check previous error
            if ($namespace_error_flag == true) {
                // add response information
                $return_array['TERM'] = $term;
            } else {
                // create URI (response will give details whether the URI exists or not)
                $return_array = $this->createURI($this->config->website, $namespace, $term);
            }

            // append information for response
            array_push($this->response_array, $return_array);

            // get next token
            $term = strtok(", ");
        }

        // report
        $this->responseReoprtByJSON(OPERATION_CREATE, $this->response_array);
    }

    /**
     * DESCRIPTION:
     *     Service by JSON URL
     *
     * IN:
     *     $operation   operation (create/delete/search)
     *     $json_url    JSON URL (structure-content describes in design document)
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     * 
     * TODO:
     *     Support other operations
     */
    function serviceByJsonURL($operation, $json_url)
    {
        if ($operation == OPERATION_CREATE) {
            // create URIs
            $this->operationCreateByJsonURL($json_url);
        } else if ($operation == OPERATION_DELETE) {
            // TODO
            $this->responseError("TODO operation.");
        } else if ($operation == OPERATION_SEARCH) {
            // TODO
            $this->responseError("TODO operation.");
        } else {
            // response error
            $this->responseError("operation not supported yet.");
        }
    }

    /**
     * DESCRIPTION:
     *     Service by CSV namespaces
     *
     * IN:
     *     $operation     operation (create/delete/search)
     *     $namespaces    a CSV list of comma-separated namespaces
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     * 
     * TODO:
     *     Support other operations
     */
    function serviceByCsvNamespaces($operation, $namespaces)
    {
        if ($operation == OPERATION_CREATE) {
            // TODO
            $this->responseError("TODO operation.");
        } else if ($operation == OPERATION_DELETE) {
            // delete namespaces
            $this->operationDeleteByCsvNamespaces($namespaces);
        } else if ($operation == OPERATION_SEARCH) {
            // TODO
            $this->responseError("TODO operation.");
        } else {
            // response error
            $this->responseError("operation not supported yet.");
        }
    }

    /**
     * DESCRIPTION:
     *     Service by namespace and terms
     *
     * IN:
     *     $operation   operation (create/delete/search)
     *     $namespace   namespace
     *     $terms       a CSV list of comma-separated values
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     NONE
     * 
     * TODO:
     *     Support other operations
     */
    function serviceByCsvTerms($operation, $namespace, $terms)
    {
        if ($operation == OPERATION_CREATE) {
            // create URIs
            $this->operationCreateByCsvTerms($namespace, $terms);
        } else if ($operation == OPERATION_DELETE) {
            // TODO
            $this->responseError("TODO operation.");
        } else if ($operation == OPERATION_SEARCH) {
            // TODO
            $this->responseError("TODO operation.");
        } else {
            // response error
            $this->responseError("operation not supported yet.");
        }
    }

    /**
     * DESCRIPTION:
     *     Get term page URL to be added
     *
     * IN:
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     URL string if sucess, else empty string
     * 
     * TODO:
     *     Make code reuse and smaller
     */
    function getAddTermURL($homepage, $namespace)
    {
        $result = "";
        $namespace_url = $homepage . $namespace;

        // ok
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $namespace_url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);

        // disable output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $html = curl_exec($ch);
        curl_close($ch);

        // parse HTML
        $doc = new DOMDocument();
        @$doc->loadHTML($html);    // symbol '@' is to ignore warning message due to the original HTML code

        // get tag <a> elements
        $a_links = $doc->getElementsByTagName("a");

        // find link
        foreach ($a_links as $link) {
            if ($link->nodeValue == "Add new property") {
                $result = dirname($homepage) . $link->getAttribute("href");
                break;
            }
        }

        return $result;
    }

    /**
     * DESCRIPTION:
     *     Get term page URL to be deleted
     *
     * IN:
     *     $homepage     homepage
     *     $namespace    namespace
     *
     * OUT:
     *     NONE
     * 
     * RETURNS:
     *     URL string if sucess, else empty string
     * 
     * TODO:
     *     Make code reuse and smaller
     */
    function getDeleteNamespaceURL($homepage, $namespace)
    {
        $result = "";
        $namespace_url = $homepage . $namespace;

        // ok
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $namespace_url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);

        // disable output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $html = curl_exec($ch);
        curl_close($ch);

        // parse HTML
        $doc = new DOMDocument();
        @$doc->loadHTML($html);    // symbol '@' is to ignore warning message due to the original HTML code

        // get tag <a> elements
        $a_links = $doc->getElementsByTagName("a");

        // find link
        foreach ($a_links as $link) {
            if ($link->nodeValue == "Edit") {
                $edit_page = dirname($homepage) . $link->getAttribute("href");    // get edit page
                $delete_page = dirname($edit_page) . "/delete";    // get delete page
                $result = $delete_page;
                break;
            }
        }

        return $result;
    }
}

// // TODO
// else if (isset($_REQUEST['json_string']) && !empty($_REQUEST['json_string'])) {
//     $json_string = $_REQUEST['json_string'];
//     $json_obj = json_decode($json_string, true);
//     foreach ($json_obj as $namespace => $terms) {
//         echo "namespace =  $namespace <br />";
// 
//         // check if namespace already exist
//         if (isNamespaceExist($config->website, $namespace) == false) {
//             echo "<p>Namespace $namespace is not exist, now created successfully<p>";
// 
//             // add namespace
//             createNamespace($config->website, $namespace);
//         }
// 
//         // add terms
//         foreach ($terms as $term) {
//             echo "term =  $term <br />";
//             createURI($config->website, $namespace, $term);
//         }
//     }
// }
// 
// // add from file (todo)
// else if (isset($_FILES['json_file']) && !empty($_FILES['json_file'])) {
//     if ($_FILES['json_file']['error'] > 0) {
//         echo "Error: " . $_FILES['json_file']['error'] . "<br />";
//         exit;
//     } else {
//         $json_file = $_FILES['json_file']['tmp_name'];
//         $json_string = file_get_contents($json_file);
//         $json_obj = json_decode($json_string, true);
//         foreach ($json_obj as $namespace => $terms) {
//             echo "namespace =  $namespace <br />";
// 
//             // check if namespace already exist
//             if (isNamespaceExist($config->website, $namespace) == false) {
//                 echo "<p>Namespace $namespace is not exist, now created successfully<p>";
// 
//                 // add namespace
//                 createNamespace($config->website, $namespace);
//             }
// 
//             // add terms
//             foreach ($terms as $term) {
//                 echo "term =  $term <br />";
//                 createURI($config->website, $namespace, $term);
//             }
//         }
//     }
// }


?>
