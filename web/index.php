<?php
require("config.inc.php");
require("func.inc.php");

require(VENDOR_PATH . 'twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

define("DELIMITER_LOGINCOOKIE_EXTERNAL", "|");

$credentialsOk      = false;
$error_message = null;

if (!isset($_SESSION['host']) || !isset($_SESSION['user']))
{
    $_SESSION['host'] = "";
    $_SESSION['user'] = "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $credentialsOk = checkLoginValid();

    if ($credentialsOk)
    {
        debug("Credentials are ok");
        storeLoginToSession();
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
    $loader = new Twig_Loader_Filesystem('templates');

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

