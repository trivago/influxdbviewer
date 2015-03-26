<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();
define("DELIMITER_LOGINCOOKIE_EXTERNAL",  "|");
require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

$loggedIn      = false;
$error_message = null;

if(!isset($_SESSION['host']) || !isset($_SESSION['user'])){
    $_SESSION['host'] = "";
    $_SESSION['user'] = "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $loggedIn = checkLoginValid();

    if ($loggedIn)
    {
        storeToSession();
        addLoginToCookie();
        redirectTo("databases.php");
    }
   else
    {
         $error_message = "Invalid login";
        // does not redirect, will end up in loginform
    }
}


try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('../app/templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('index.twig');

    // set template variables
    // render template
    echo $template->render(
        array(

            'title' => "Welcome",
            'error' => $error_message,
            'user'  => $_SESSION['user'],
            'host'  => $_SESSION['host'],
        )
    );
}
catch (Exception $e)
{
    die ('ERROR: ' . $e->getMessage());
}

function redirectTo($path)
{
    header("Location: " . $path);
    exit();
}

function checkLoginValid()
{
    $url        = "http://" . $_POST['host'] . ":8086/db?u=" . $_POST['user'] . "&p=" . $_POST['pw'];
    $httpResult = getUrlContent($url);
    #print "Url " . $url . " -> " ;
    #print_r($httpResult);
    return (200 == $httpResult['status_code']);
}

function storeToSession()
{
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['user'] = $_POST['user'];
    $_SESSION['pw']   = $_POST['pw'];
}


function addLoginToCookie(){
    $cookie_name = "last_logins";
    $saveMe = $_SESSION['user'] . "@" . $_SESSION['host'];
    $oldValue = readCookie($cookie_name);
    if(!cookieContainsLogin($oldValue, $saveMe)){
        $newValue = $oldValue .DELIMITER_LOGINCOOKIE_EXTERNAL . $saveMe;
        setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
    }
}

function cookieContainsLogin($oldValue, $str){
    $logins = explode(DELIMITER_LOGINCOOKIE_EXTERNAL, $oldValue);
    foreach($logins as $login){
        if ($login == $str){
            return true;
        }
    }
    return false;
}

function readCookie($cookie_name){
    return (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : "";

}

function getUrlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $data       = curl_exec($ch);
    $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status_code' => $statuscode, 'results' => $data];
}
