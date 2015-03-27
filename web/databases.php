<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();
session_start();
if (!isset($_SESSION['host']) || empty($_SESSION['host']))
{ // TODO same for username
    redirectTo("index.php");
}
require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();

$databases = getListOfDatabases();

if (isset($_REQUEST['database']) && !empty($_REQUEST['database']))
{
    if (in_array($_REQUEST['database'], $databases))
    {
        $_SESSION['database'] = $_REQUEST['database'];
        redirectTo("query.php");
    }
}

try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('../app/templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('databases.twig');

    // set template variables
    // render template
    echo $template->render(
        array(
            'title'     => "Databases",
            'databases' => $databases,

            'user'      => $_SESSION['user'],
            'host'      => $_SESSION['host'],
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
    die();
}

function getListOfDatabases()
{
    $url        = "http://" . $_SESSION['host'] . ":8086/db?u=" . $_SESSION['user'] . "&p=" . $_SESSION['pw'];
    $httpResult = getUrlContent($url);

    if (200 == $httpResult['status_code'])
    {

        $json   = json_decode($httpResult['results']);
        $result = array();
        foreach ($json as $value)
        {
            $result[] = $value->name;
        }

        return $result;
    }
    else
    {
        // TODO error handling
    }
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
