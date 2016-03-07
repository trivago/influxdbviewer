<?php
require("config.inc.php");
require("func.inc.php");
require("renderobject.class.php");

require __DIR__ . '/vendor/autoload.php';
session_start();

if (!isset($_SESSION['host']) || empty($_SESSION['host']) || !isset($_SESSION['user']) || empty($_SESSION['user']) || !isset($_SESSION['pw']) || empty($_SESSION['pw']))
{
    redirectTo("index.php");
}
if (!isset($_SESSION['database']) || empty($_SESSION['database']))
{
    redirectTo("databases.php");
}

define('DELIMITER_COMMANDCOOKIE_INTERNAL', "#");
define('DELIMITER_COMMANDCOOKIE_EXTERNAL', "|");

try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('query.twig');

    if (!empty($_REQUEST['query']))
    {
        $query = $_REQUEST['query'];
        $query = autoLimit($query);
        $render = getDatabaseResults($query);          
    } 
    else 
    {
        debug("Empty query, aborting.");
    }

    echo $template->render(
        (array) $render
    );
    
} 
catch (Exception $e)
{
    die ('ERROR: ' . $e->getMessage());
}



