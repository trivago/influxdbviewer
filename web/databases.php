<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();

require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

if($_POST){
	  $loggedIn      = false;
        $error_message = null;
        // session_start();

        require_once("app/login.inc.php");

        if ($loggedIn)
        {
            redirectTo("databases.php");
        } else {
            $_SESSION['host'] = "";
            $_SESSION['user'] = "";
            // does not redirect, will end up in loginform
        }
} else {
	$_SESSION['host'] = "";
            $_SESSION['user'] = "";
}

try {
  // specify where to look for templates
  $loader = new Twig_Loader_Filesystem('../app/templates');
  
  // initialize Twig environment
  $twig = new Twig_Environment($loader);
  
  // load template
  $template = $twig->loadTemplate('index.twig');
  
  // set template variables
  // render template
  echo $template->render(array(
    
                'title'      => "Welcome",
                'error'      => null,
                'user'       => $_SESSION['user'],
                'host'       => $_SESSION['host'],
  ));
  
} catch (Exception $e) {
  die ('ERROR: ' . $e->getMessage());
}

function redirectTo($path){
	header("Location: " . $path);
die();
}