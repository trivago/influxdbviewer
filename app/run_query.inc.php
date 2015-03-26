<?php

define('MAX_RESULT_AGE_CACHE_SECONDS', 30);
define('RESULTS_PER_PAGE', 50);

if (!empty($_REQUEST['query']))
{
    $query           = $_REQUEST['query'];
    $feedback        = getDatabaseResults($query);
    $results         = $feedback['result'];
    $timestamp       = $feedback['timestamp'];
    $is_cached       = $feedback['is_cached'];
    $page            = $feedback['page'];
    $is_series_list  = isSeriesList($query);
    $number_of_pages = $feedback['number_of_pages'];
    $error_message   = $feedback['error_message'];
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
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {
            $json            = json_decode($httpResult['results']);
            print_r($json[0]);
            $columns         = $json['columns'];
            $datapoints      = $json['points'];
            $results         = ['columns' => $columns, 'datapoints' => $datapoints];
            $number_of_pages = count($datapoints);
            $feedback        = [
                'timestamp'       => $now,
                'results'         => $results,
                'is_cached'       => false,
                'page'            => 1,
                'number_of_pages' => $number_of_pages,
                'error_message'   => null
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
    $cookie_name = "commands";
    $saveMe = $ts . "/" . $number_of_pages . "/" . $command;
    $oldValue = readCookie($cookie_name);
    $newValue = $oldValue . "|" . $saveMe;
    setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
}

function readCookie($cookie_name){
    return (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : "";

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
