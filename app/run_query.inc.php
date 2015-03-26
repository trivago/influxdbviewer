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
    $feedback      = '';
    $cache_results = searchCache($query);

    if ($cache_results != null && !$_REQUEST['ignore_cache'])
    {
        $cache_results['is_cached'] = true;
        $feedback                   = $cache_results;
    }
    else
    {
        $now        = mktime();
        $url        = "http://" . $_SESSION['host'] . "/db/" . $_SESSION['database'] . "/series?u="
            . $_SESSION['user'] . "&p=" . $_SESSION['pw'] . "&q=" . urlencode($query);
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {
            $json            = json_decode($httpResult['results']);
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
        }
        else
        {
            // TODO set error message if it contains any
        }
    }
    if ($feedback['error_message'] == null)
    {
        $page          = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limitedResult = limitResult($_REQUEST['page'], $feedback['results']);

        if ($limitedResult != null)
        {
            $feedback['page']    = $_REQUEST['page'];
            $feedback['results'] = $limitedResult;
        }
    }

    return $feedback;
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

function limitResult($page, $data)
{

	if(empty($page) || !is_numeric($page) || $page < 1 ){
		$page = 1;
	}

	$start = ($page - 1) * RESULTS_PER_PAGE;
	return array_slice ( $data, $start, RESULTS_PER_PAGE );
    
}