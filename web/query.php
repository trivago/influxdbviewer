<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();

require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();


try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('../app/templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('query.twig');

    $query           = "";
    $results         = null;
    $is_series_list  = false;
    $timestamp       = 0;
    $is_cached       = false;
    $error_message   = null;
    $page            = 1;
    $number_of_pages = 1;

    require_once("../app/run_query.inc.php");

    // set template variables
    // render template
    echo $template->render(
        array(
                'title'           => 'Results',
                'query'           => $query,
                'results'         => $results,
                'is_series_list'  => $is_series_list,
                'timestamp'       => $timestamp,
                'is_cached'       => $is_cached,
                'error_message'   => $error_message,
                'page'            => $page,
                'number_of_pages' => $number_of_pages,
                'user' => $_SESSION['user'],
                'host' => $_SESSION['host'],
                'database' => $_SESSION['database'],
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