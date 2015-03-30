<?php


require('../vendor/twig/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
session_start();

define("DEBUG",false); // TODO
   define('MAX_RESULT_AGE_CACHE_SECONDS', 30);
define('RESULTS_PER_PAGE', 30);
define("AUTO_LIMIT", true);
define("AUTO_LIMIT_VALUE", 1000);


// ----------------------- End of configuration ----------------------------------

if (!isset($_SESSION['host']) || empty($_SESSION['host']) || !isset($_SESSION['user']) || empty($_SESSION['user']) || !isset($_SESSION['pw']) || empty($_SESSION['pw']) )
{ 
    redirectTo("index.php");
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

    $query             = "";
    $results           = null;
    $is_series_list    = false;
    $timestamp         = 0;
    $is_cached         = false;
    $error_message     = null;
    $page              = 1;
    $number_of_pages   = 1;
    $number_of_results = -1;
    $columns           = [];
    $datapoints        = [];


// 

if (!empty($_REQUEST['query']))
{
    $query             = $_REQUEST['query'];
    $query = autoLimit($query);
    $feedback          = getDatabaseResults($query);
    $columns           = $feedback['results']['columns'];
    $datapoints        = $feedback['results']['datapoints'];
    $timestamp         = $feedback['timestamp'];
    $is_cached         = $feedback['is_cached'];
    $page              = $feedback['page'];
    $is_series_list    = isSeriesList($query);
    $number_of_pages   = $feedback['number_of_pages'];
    $number_of_results = $feedback['number_of_results'];
    $error_message     = $feedback['error_message'];
    
}

    // set template variables
    // render template
    echo $template->render(
        array(
            'title'             => 'Results',
            'query'             => $query,
            'datapoints'        => $datapoints,
            'columns'           => $columns,
            'is_series_list'    => $is_series_list,
            'timestamp'         => $timestamp,
            'is_cached'         => $is_cached,
            'error_message'     => $error_message,
            'page'              => $page,
            'number_of_pages'   => $number_of_pages,
            'number_of_results' => $number_of_results,
            'user'              => $_SESSION['user'],
            'host'              => $_SESSION['host'],
            'database'          => $_SESSION['database'],
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

function autoLimit($query){
    
    if (AUTO_LIMIT && isSelectQuery($query) && !isLimited($query)){
        $query .= " LIMIT " . AUTO_LIMIT_VALUE;
    }
    return $query; 
}

function isSelectQuery($query){
    return preg_match('/select .*/i', $query)>0;
}

function isLimited($query){
    return preg_match('/select .* limit \d+/i', $query)>0;
}

function isSeriesList($query)
{
    // return strrpos(strtolower($query), "list series") !== false;
    return preg_match('/list series.*/i', $query)>0;
}


function addCommandToCookie($command, $ts, $number_of_pages)
{
    $cookie_name = "last_commands";
    $saveMe      = $ts . DELIMITER_COMMANDCOOKIE_INTERNAL . $number_of_pages . DELIMITER_COMMANDCOOKIE_INTERNAL . $command;
    #debug("New cookie section: " . $saveMe . "<br>";
    $oldValue = readCookie($cookie_name);
    if (!cookieContainsCommand($oldValue, $command))
    {
        $newValue = $oldValue . DELIMITER_COMMANDCOOKIE_EXTERNAL . $saveMe;
        # debug("Old cookie section: " . $oldValue . "<br>";
        #debug("Full cookie section: " . $newValue . "<br>";

        setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
    }
}

function readCookie($cookie_name)
{
    return (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : "";
}

function cookieContainsCommand($oldValue, $str)
{
    $commands = explode(DELIMITER_COMMANDCOOKIE_EXTERNAL, $oldValue);

    foreach ($commands as $command)
    {
        #    debug("cookieContainsCommand " . $command . " to be split by " . DELIMITER_COMMANDCOOKIE_INTERNAL );
        $tokens = explode(DELIMITER_COMMANDCOOKIE_INTERNAL, $command);

//         debug("cookieContainsCommand " . $tokens[2] . " vs " . $str );
        if (sizeof($tokens) == 3 &&$tokens[2] == $str)
        {
            return true;
        }
    }

    return false;
}




function saveResultsToCache($query, $results, $timestamp, $number_of_pages)
{
    $_SESSION['cache'][$query] = ['timestamp' => $timestamp, 'results' => $results, 'number_of_pages' => $number_of_pages];
}

function searchCache($query)
{
    if (isset($_SESSION['cache'][$query]) && isFreshResult($_SESSION['cache'][$query]['timestamp']))
    {
        return $_SESSION['cache'][$query];
    }
}

function isFreshResult($timestamp)
{
    return time() - $timestamp < MAX_RESULT_AGE_CACHE_SECONDS;
}


function limitResult($page, $data)
{

    if (empty($page) || !is_numeric($page) || $page < 1)
    {
        $page = 1;
    }

    $start = ($page - 1) * RESULTS_PER_PAGE;
    debug("Limiting result to " . $start . " - " . ($start + RESULTS_PER_PAGE));
    $subset = array_slice($data, $start, RESULTS_PER_PAGE);
    debug("Subset has " . sizeof($subset) . " results"); 
    return $subset;
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



function getDatabaseResults($query)
{
    
    $feedback                  = [];
    $feedback['error_message'] = null;


    $ignore_cache = true; // TODO isset($_REQUEST['ignore_cache']) && $_REQUEST['ignore_cache'];

    if (!$ignore_cache && $cache_results = searchCache($query) != null)
    {
        
        debug("Got data from cache. ");
        
        $feedback                  = $cache_results;
        $feedback['is_cached']     = true;
        $feedback['error_message'] = null;
    }
    else
    {
        debug("Getting data from db. ");
        $now = time();
        $url = "http://" . $_SESSION['host'] . ":8086/db/" . $_SESSION['database'] . "/series?u="
            . $_SESSION['user'] . "&p=" . $_SESSION['pw'] . "&q=" . urlencode($query);

       
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {
            $json = json_decode($httpResult['results']);

            $columns           = $json[0]->columns;
            $datapoints        = $json[0]->points;
            $results           = ['columns' => $columns, 'datapoints' => $datapoints];
            $number_of_results = count($datapoints);
            $number_of_pages   = ceil($number_of_results / RESULTS_PER_PAGE);
            debug("Got ". $number_of_results . " results.");
            $feedback          = [
                'timestamp'         => $now,
                'results'           => $results,
                'is_cached'         => false,
                'page'              => 1,
                'number_of_pages'   => $number_of_pages,
                'number_of_results' => $number_of_results,
                'error_message'     => null
            ];
          
            saveResultsToCache($query, $results, $now, $number_of_pages);
            addCommandToCookie($query, $now, $number_of_pages);
        }
        else
        {
            // TODO set error message if it contains any
        }
    }
    if ($feedback['error_message'] == null)
    {
        $page          = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        debug("Page is " . $_REQUEST['page'] . " -> " .$page );

        $limitedResult = limitResult($page, $feedback['results']['datapoints']);

        if (!empty($limitedResult))
        {
            debug("Setting limited result"); 
            $feedback['page']    = $page;
            $feedback['results']['datapoints'] = $limitedResult;
        } else {
            debug("Subset creation failed, result empty");
        }
    }

    return $feedback;
}

function debug($text){
    if(DEBUG){
        print $text . "<br>";
    }
}
