<?php
require("config.inc.php");
require("func.inc.php");

session_start();
if (!isset($_SESSION['host']) || empty($_SESSION['host']) || !isset($_SESSION['user']) || empty($_SESSION['user']))
{
    redirectTo("index.php");
}
require(VENDOR_PATH . 'twig/twig/lib/Twig/Autoloader.php');
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
    $loader = new Twig_Loader_Filesystem('templates');

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


