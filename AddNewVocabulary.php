<?php

// Description: 
//    Automatically fill on Neologism forms to create URIs for OpenISDM internel ontology (vocabularies and terms)
//
// Requirement: 
//    1. Install 'php5-curl' for 'curl_init()' function.
//       Command on Ubuntu: sudo apt-get install php5-curl

include("./lib/LIB_http.php");

echo "Add new vocabulary from Neologism" . PHP_EOL;

//// Webbot Diagnostic Page
//$action = "http://www.WebbotsSpidersScreenScrapers.com/form_analyzer.php";

// web pages to crawl
$link_neologism = "https://ph.ccg.tw/neologism/";
$link_login = "https://ph.ccg.tw/neologism/?q=user";
$link_namespace = "https://ph.ccg.tw/neologism/node/add/neo-vocabulary";
$link_term = "https://ph.ccg.tw/neologism/node/11/add-property";

// Login Neologism
function login($username, $passwd) {

    //$action = "https://ph.ccg.tw/neologism/?q=user";
    global $link_login;
    $action = $link_login;
    $method = "POST";
    $ref = "";
    $data_array['name'] = $username;
    $data_array['pass'] = $passwd;
    $data_array['form_id'] = "user_login";

    // save cookie ('cookie.txt' file path describes in file './lib/LIB_http.php')
    $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);


    echo "<br/>";
    echo "<br/>";
    //print_r($response);
}

// create namespace by using Neologism
function createNamespace($prefix, $title) {

    //$action = "https://ph.ccg.tw/neologism/node/add/neo-vocabulary";
    global $link_namespace;
    $action = $link_namespace;
    $ref = "";
    $method = "GET";
    $data_array = null;
    $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);

    // get token from GET method
    $token = explode("\n",$response['FILE']);
    //echo $token[132];
    $token = explode("value=\"",$token[132]);
    $token = explode("\"",$token[1]);
    $token = $token[0]; // this is exactly what we want

    // set required parameters
    $data_array = null;
    $data_array['prefix'] = $prefix;
    $data_array['title'] = $title;
    $data_array['form_token'] = $token;
    $data_array['form_id'] = "neo_vocabulary_node_form";
    $data_array['authors%5B%5D'] = "1";
    $data_array['status'] = "1";    // make it Published

    // set to POST method to fill form automatically
    $method = "POST";
    $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);


    echo "<br/>";
    echo "<br/>";
    print_r($response);
}

// create term by using Neologism
// returns: URI
function createTerm($title, $label) {

    //$action = "https://ph.ccg.tw/neologism/node/11/add-property";
    global $link_term;
    $action = $link_term;
    $ref = "";
    $method = "GET";
    $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);

    // get token from GET method
    $token = explode("\n",$response['FILE']);
    //echo $token[130];
    $token = explode("value=\"",$token[130]);
    $token = explode("\"",$token[1]);
    $token = $token[0]; // this is exactly what we want

    //// test-Begin
    //print_r($token);
    //exit;
    //// test-End


    // get build_id from GET method
    $build_id = explode("\n",$response['FILE']);
    //echo $build_id[129];
    $build_id = explode("value=\"",$build_id[129]);
    $build_id = explode("\"",$build_id[1]);
    $build_id = $build_id[0]; // this is exactly what we want

    //// test-Begin
    //print_r($build_id);
    //exit;
    //// test-End



    // set required parameters
    $data_array = null;
    $data_array['title'] = $title;
    $data_array['field_label%5B0%5D%5Bvalue%5D'] = $label;
    $data_array['form_token'] = $token;
    $data_array['form_id'] = "neo_property_node_form";

    // set to POST method to fill form automatically
    $method = "POST";
    $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);

    echo "<br/>";
    echo "<br/>";
    print_r($response);

    // todo
    $uri = "";
    return $uri;
}

// search term
// returns: URI
function searchTerm($term) {
    $URIs = array();
    //$URIs = "";


    $host = 'ph.ccg.tw';
    $username = 'neologism';
    $passwd = 'neologism';
    $dbname = 'neologism';

    // problem suggestion: http://blog.sina.com.cn/u/1708861944
    // qq
    // php5-mysql is required for mysqli()
    $mysqli = new mysqli($host, $username, $passwd, $dbname);
    //$mysqli = new mysqli($hostdb, $userdb, $passdb, $namedb) or die("Connect failed: " . $mysqli->connect_error . "<br/>");

    /* check connection */
    if ($mysqli->connect_errno) {
        printf("Connect failed: %s\n", $mysqli->connect_error);
        exit();
    }

    // get namespace URIs
    global $link_neologism;
    $neologism_homepage = $link_neologism;
    $namespaces = array();
    $sql = "SELECT * FROM `neologism_vocabulary`";
    //$sql = "SELECT * FROM `neologism_vocabulary` WHERE `prefix`";
    if ($result = $mysqli->query($sql)) {
        while($obj = $result->fetch_assoc()){ 
            $uri = $neologism_homepage . $obj['prefix'] . "#";
            array_push($namespaces, $uri);
        }
    }
    print_r($namespaces);
    echo "<br/>";


// TODO: cwyu current
//    foreach ($namespaces as $namespace) {
//        echo "URI: " . $namespace . "<br/>";
//
//        $contents = file_get_contents($namespace);
//        echo "<br/>Begin<br/>";
//        echo $contents;
//        echo "<br/>End<br/>";
//
//    }

    foreach ($namespaces as $namespace) {
        //global $link_namespace;
        $action = $namespace;
        $ref = "";
        $method = "GET";
        $data_array = null;
        $response = http($target=$action, $ref, $method, $data_array, EXCL_HEAD);

echo "<br/>AA<br/>";
echo '$namespace = ' . $namespace;
echo "<br/>BB<br/>";

        echo "<br/>Begin<br/>";
        //echo $response['FILE'];

        //preg_match("@^https?://@i", $response['FILE'], $matches, PREG_OFFSET_CAPTURE, 3);
        //preg_match("@^https?://@i", $response['FILE'], $matches);
        //preg_match("'/^(http|https|ftp)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?/i'", $response['FILE'], $matches);

        //preg_match_all("@(?<!href=\")https?://.*(?<!\")@i", $response['FILE'], $matches);
        //preg_match_all("@(?<!href=\">)https?://.*(?<!\">)@i", $response['FILE'], $matches);
        //preg_match_all("@(?!href=\")https?://.*\"@i", $response['FILE'], $matches);

        // good
        //preg_match_all("@href=\"https?://.*\"@i", $response['FILE'], $matches);
        //preg_match_all("@href=\"https?://.+\"@i", $response['FILE'], $matches);
        //preg_match_all('@<a href="(https?://.+)"@i', $response['FILE'], $matches);
        //preg_match_all('@<a href="(https?://.*)"@i', $response['FILE'], $matches);

        //preg_match_all("@href=\"https?://.+\"@i", $response['FILE'], $matches);
        preg_match_all('@href="https?://.+"@i', $response['FILE'], $matches);


// $text=file_get_contents('https://ph.ccg.tw/neologism/openisdm#');
// file_put_contents("output/xd.html", $text);
// //$text=file_get_contents($namespace);
//     // echo "<br/>TTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTT<br/>";
//     // echo $text;
//     // echo "<br/>ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ<br/>";
// preg_match_all('@<a href="(https?://.*)"@i', $text, $matches);

// // TODO: http://stackoverflow.com/questions/4001328/php-regex-to-get-string-inside-href-tag
// $url = preg_match_all('/<a href="(.+)">/', $response['FILE'], $match);
// print_r($match);
// $info = parse_url($match[1]);
// //print_r($info);
// //echo $info['scheme'].'://'.$info['host']; // http://www.mydomain.com

        for ($i = 0; $i < count($matches); $i++) {
            $matches[$i] = preg_replace('/href="(.*?)"/', "\\1", $matches[$i]);
//            $xd = (string) ($matches[$i]);
//            echo "<br/>" . '$match[i] = ' . "$xd" . "<br/>";
        }

/*
        foreach ($matches as $i => $value) {
            $matches[$i] = preg_replace('/href="(.*?)"/', "\\1", $matches[$i]);
            //$match = preg_replace('/href=\"(.*?)\"/', "\\1", $match);
            echo "<br/>" . '$match[i] = ' . "$i" . "<br/>";
            //echo "<br/>" . '$match[i] = ' . "$matches[$i]" . "<br/>";
        }
*/

        // TODO: output may different each time
        print_r($matches);

        //print_r(preg_grep("@^https?://@i", explode("\n", $response['FILE'])));
        //print_r(preg_grep("'/^(http|https|ftp)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?/i'", explode("\n", $response['FILE'])));

        //// good
        //print_r(preg_grep("@https?://@i", explode("\n", $response['FILE'])));

        echo "<br/>End<br/>";

        $uri = $namespace . $term;
        foreach ($matches as $key => $value) {
            echo "<br/>GG<br/>";
     //       echo "Array: $key, $value";
//            print_r($value);
            //print_r($key);
            echo "<br/><br/>QQ<br/>";
//            //if ($uri === $key) {
//            echo "<br/>" . '$uri = ' . $uri;
//            //echo "<br/>" . '$key = ' . "$key" . "<br/>";
//            echo "<br/>" . '$value = ' . $value . "<br/>";

            if ($uri == $key) {
            //if ($uri == $value) {
                array_push($URIs, $uri);
            }
        }

//        // get token from GET method
//        $token = explode("\n",$response['FILE']);
//        //echo $token[132];
//        $token = explode("value=\"",$token[132]);
//        $token = explode("\"",$token[1]);
//        $token = $token[0]; // this is exactly what we want
    }

//echo "<br/>";
//echo "<br/>Begin<br/>";
//echo file_get_contents($uri);
//echo "<br/>End<br/>";
//echo "<br/>";
// todo
echo "XDDDDDDDDDDDDD GGS<br/>";
print_r($URIs);
exit;

    // /* Create table doesn't return a resultset */
    // if ($mysqli->query("CREATE TEMPORARY TABLE myCity LIKE City") === TRUE) {
    //     printf("Table myCity successfully created.\n");
    // }

    /* Select queries return a resultset */
    //if ($result = $mysqli->query("SELECT * FROM `node` LIMIT 0, 30")) {
    //$sql = "SELECT * FROM `node` WHERE `title` = 'Town' LIMIT 0, 1000";    // todo: greater than 1000
    //$sql = "SELECT * FROM `node` LIMIT 0, 1000";    // todo: greater than 1000
//echo "<br/>" . "XXXXXXXXXXXXX" . "<br/>";
//echo "<br/>" . '$term = ' . $term . "<br/>";
//    $sql = "SELECT * FROM `node` WHERE `title` = $term";
//echo "<br/>" . '$sql = ' . $sql . "<br/>";
//    $sql = "SELECT * FROM `node` WHERE `title` = 'Town'";
//echo "<br/>" . '$sql = ' . $sql . "<br/>";

    // query description: 'neo_property' type for term records; 'neo_vocabulary' type for namespace records
    $sql = "SELECT * FROM `node` WHERE `type` = 'neo_property' AND `title` = '$term'";
    //$sql = "SELECT * FROM `node` WHERE `title` = '$term'";

//echo "<br/>" . '$sql = ' . $sql . "<br/>";
    //$sql = "SELECT * FROM `node`";
    if ($result = $mysqli->query($sql)) {
        printf("Select returned %d rows.\n", $result->num_rows);
        echo "<br/>";

        if ($result->num_rows == 0) {
            echo "Cannot find any URI for term='$term'";
            echo "<br/>";
            // todo: return null object & ask for build ones
        } else {

            echo "<br/>";
            global $link_neologism;
            while ($obj = $result->fetch_assoc()){
                echo $obj['title'] . "<br/>";

                foreach ($namespaces as $namespace) {
                    $uri = $namespace . $obj['title'];
                    echo "URI: " . $uri . "<br/>";
                }

                //            $namespace_uri = $link_neologism . "openisdm" . "#";
                //            //$uri = $namespace_uri . $obj['title'];
                //            $index = $obj['title'];
                //            $uri[$index] = $namespace_uri . $obj['title'];
            }
        }

        /* free result set */
        $result->close();
    } else {
        echo "[Query Failure] $sql";
        //echo "Cannot be found URIs for '$term'";
        echo "<br/>";
        // todo: return null object & ask for build ones
    }


    // $sql = "SELECT * FROM `node` LIMIT 0, 30 ";
    // $result = mysql_query($sql);
    // if (!$result) {
    //     die('Invalid query: ' . mysql_error());
    // } else {
    //     echo "<br/>";
    //     echo "<br/>";
    //     print_r($result);
    //     echo "<br/>";
    //     echo "<br/>";
    // }


    //$sql = "SELECT * FROM `node` LIMIT 0, 30 ";
    //
    //SELECT * 
    //FROM  `node` 
    //LIMIT 0 , 30
    //
    //DELETE FROM `neologism`.`node` WHERE `node`.`nid` = 107;



    return $uri;
}

// login (will get cookie)
$username = "admin";
$passwd = "iis404";
login($username, $passwd);

// // create namespace
// $prefix = "test";    // note: cannot be longer than 10 characters
// $title = "test";
// createnamespace($prefix, $title);    // todo: author(anonymous -> admin)

// // create term
// $title = "newTerm";
// $label = "newTerm";
// createTerm($title, $label);

// search term
// todo: count = 0,1,2
//$term = "County";
//$term = "CountyXD";
$term = "Town";
$uri = searchTerm($term);
//echo "<br/>" . "URI = " . $uri . "<br/>";
echo "<br/>";
print_r($uri);
echo "<br/>";





// https://ph.ccg.tw/neologism/?q=user&user-login=admin&edit-pass-wrapper=iis404
// http://www.webbotsspidersscreenscrapers.com/

// if (file_exists('./'. conf_path() .'/settings.php')) {
//   include_once './'. conf_path() .'/settings.php';
// }

?>
