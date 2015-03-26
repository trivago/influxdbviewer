<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();

require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();
$_SESSION['host'] = "horst";
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
                'user'       => "root",
                'host'       => $_SESSION['host'],
  ));
  
} catch (Exception $e) {
  die ('ERROR: ' . $e->getMessage());
}
