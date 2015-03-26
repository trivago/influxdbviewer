<?php

define('MAX_RESULT_AGE_CACHE_SECONDS', 30);
define('RESULTS_PER_PAGE', 50);
define('DELIMITER_COMMANDCOOKIE_INTERNAL', "#");
define('DELIMITER_COMMANDCOOKIE_EXTERNAL', "|");

if (!empty($_REQUEST['query']))
{
    $query           = $_REQUEST['query'];
    $feedback        = getDatabaseResults($query);
    $columns         = $feedback['results']['columns'];
    $datapoints         = $feedback['results']['datapoints'];
    $timestamp       = $feedback['timestamp'];
    $is_cached       = $feedback['is_cached'];
    $page            = $feedback['page'];
    $is_series_list  = isSeriesList($query);
    $number_of_pages = $feedback['number_of_pages'];
    $number_of_results = $feedback['number_of_results'];
    $error_message   = $feedback['error_message'];
    print_r($results);
}

function getDatabaseResults($query)
{
    $debug = true; // TODO
    $feedback      = [];
    $feedback['error_message'] = null;

    

    $ignore_cache = true; // TODO isset($_REQUEST['ignore_cache']) && $_REQUEST['ignore_cache'];

    if(!$ignore_cache && $cache_results = searchCache($query) != null)
    {
        if ($debug) print "Got data from cache. "; 
        $feedback                   = $cache_results;
        $feedback['is_cached'] = true;
        $feedback['error_message'] = null;
    }
    else
    {
         if ($debug) print "Getting data from db. "; 
        $now        = mktime();
        $url        = "http://" . $_SESSION['host'] . ":8086/db/" . $_SESSION['database'] . "/series?u="
            . $_SESSION['user'] . "&p=" . $_SESSION['pw'] . "&q=" . urlencode($query);

        if($debug) print $url;
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {
            $json            = json_decode($httpResult['results']);
           
            $columns         = $json[0]->columns;
            $datapoints      = $json[0]->points;
            $results         = ['columns' => $columns, 'datapoints' => $datapoints];
            $number_of_results = count($datapoints);
            $number_of_pages = ceil($number_of_results / RESULTS_PER_PAGE);
            $feedback        = [
                'timestamp'       => $now,
                'results'         => $results,
                'is_cached'       => false,
                'page'            => 1,
                'number_of_pages' => $number_of_pages,
                'number_of_results' => $number_of_results,
                'error_message'   => null
            ];
            //  print_r($feedback);
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
        $limitedResult = limitResult($page, $feedback['results']);

        if ($limitedResult != null)
        {
            $feedback['page']    = $_REQUEST['page'];
            $feedback['results'] = $limitedResult;
        }
    }

    return $feedback;
}

function addCommandToCookie($command, $ts, $number_of_pages){
    $cookie_name = "last_commands";
    $saveMe = $ts . DELIMITER_COMMANDCOOKIE_INTERNAL . $number_of_pages . DELIMITER_COMMANDCOOKIE_INTERNAL. $command;
    print "New cookie section: " . $saveMe . "<br>";
    $oldValue = readCookie($cookie_name);
    if(!cookieContainsCommand($oldValue, $command)){
    $newValue = $oldValue . DELIMITER_COMMANDCOOKIE_EXTERNAL . $saveMe;
    print "Old cookie section: " . $oldValue . "<br>";
    setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
    }
}

function readCookie($cookie_name){
    return (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : "";

}

function cookieContainsCommand($oldValue, $command){
    $commands = split($oldValue, DELIMITER_COMMANDCOOKIE_EXTERNAL);
    for($command in $commands){
        $tokens = split($command, DELIMITER_COMMANDCOOKIE_INTERNAL);
        if ($token[2] == $command){
            return true;
        }
    }
    return false;
}

function isSeriesList($query)
{
    $i = strrpos(strtolower($query), "list series");

    return $i !== false;
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
    return mktime() - $timestamp < MAX_RESULT_AGE_CACHE_SECONDS;
}


function limitResult($page, $data)
{

	if(empty($page) || !is_numeric($page) || $page < 1 ){
		$page = 1;
	}

	$start = ($page - 1) * RESULTS_PER_PAGE;
	return array_slice ( $data, $start, RESULTS_PER_PAGE );
    
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
