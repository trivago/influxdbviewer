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


$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    debug("Received post data");
    if (isset($_POST['annotation_database']) && !empty($_POST['annotation_database']))
    {
        if (in_array($_POST['annotation_database'], $databases))
        {
            $_SESSION['annotation_database'] = $_POST['annotation_database'];
            debug("Setting annotation database to " . $_POST['annotation_database'] );
            $error = sendAnnotation($_POST['timestamp'], $_POST['tags'], $_POST['payload'], $_POST['title'], $_POST['seriesname']);
            $message = (!$error) ? "Annotation added. " : "Failure when adding the annotation: " . $error;
        } else {
            debug("Annotation database not valid, aborting: " . $_POST['annotation_database'] );
        }
    } else {
        debug("Annotation database not found, aborting. ");
        debug($_POST);
    }
    

}

if(!isset($_SESSION['annotation_database']) || empty($_SESSION['annotation_database'])){
    $_SESSION['annotation_database'] = "events";
    debug("Did not find an annotation database in the session, defaulting to 'events'");
}

try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('add_annotation.twig');

    // set template variables
    // render template
    echo $template->render(
        array(
            'title'     => "Select an annotation ",
            'databases' => $databases,
            'annotation_database' => $_SESSION['annotation_database'],
            'user'      => $_SESSION['user'],
            'host'      => $_SESSION['host'],
            'message' => $message // TODO use in template
        )
    );
}
catch (Exception $e)
{
    die ('ERROR: ' . $e->getMessage());
}


