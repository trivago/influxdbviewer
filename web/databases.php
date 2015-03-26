<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();

require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

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
    $template = $twig->loadTemplate('index.twig');

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
