<?php
//$app = require __DIR__.'/../app/app.php';
// $app->run();

require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();
$databases = getListOfDatabases();

if(isset($_GET['database']){
	  
          // TODO if database list is empty: show warning and refuse further commandos

    if (in_array($_REQUEST['database'], $databases))
    {
        $_SESSION['database'] = $_REQUEST['database'];
        redirectTo("query.php");
    }
} else {


try {
  // specify where to look for templates
  $loader = new Twig_Loader_Filesystem('../app/templates');
  
  // initialize Twig environment
  $twig = new Twig_Environment($loader);
  
  // load template
  $template = $twig->loadTemplate('databases.twig');
  
  $databases = null;
 require_once("list_databases.inc.php");

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
}
function redirectTo($path){
	header("Location: " . $path);
die();
}

function getListOfDatabases()
{
	$host = $session->get('host');
    $url        = "http://" . $_SESSION['host'] . "/db/?u=" . $_SESSION['user'] . "&p=" . $_SESSION['pw'];
    print ("URRREL " . $url);
    $httpResult = getUrlContent($url);

    if (200 == $httpResult['status_code'])
    {

        $json = json_decode($httpResult['results']);
        print_r($json);
        $result = array();
        foreach ($json as $key => $value)
        {
            $result[] = $value;
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