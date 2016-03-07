<?php

require_once('resultset.class.php');

function redirectTo($path)
{
    header("Location: " . $path);
    die();
}

function sendAnnotation($timestamp, $tags, $text, $title, $name)
{
    /*
    For version 0.8:
        curl -X POST -d ''  'http://10.1.3.220:8086/db/annotations/series?u=root&p=root&time_precision=s'
    For version 0.9:
         curl -XPOST 'http://localhost:8086/write'    */

    $payload = createAnnotationBody($name, $timestamp, $tags, $text, $title);
    $precision = calculatePrecision($timestamp);
    if (($_SESSION['is_new_influxdb_version'])) {
        $url = "TODO"; // TODO
    } else {
        $url = "http://" . $_SESSION['host'] . ":8086/db/" . $_SESSION['annotation_database'] . "/series?u=" . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']) . "&time_precision=" . $precision;
    }

    $httpResult = runHttpRequest($url, $payload);
    $success = 200 == $httpResult['status_code'];
    if (!$success) {
        debug("Error when setting annotation: " . $url . " => " . $httpResult['status_code'] . " " . $httpResult['results']);
        debug("Payload: " . $payload);
    }
    return ($success) ? "" : $httpResult['results'];
}


/*
For version 0.8:

curl -X POST -d '[{ "name" : "list_series_foo",
    "columns" : ["time", "tags", "text", "title"],
    "points" : [
      [1427976200, "", "Di 24. Feb 12:57:54 CET 2015", "Run 1 Start"]
     ]  }]'  'http://192.168.35.85:8086/db/events/series?u=root&p=root&time_precision=s'

For version 0.9:

    {
    "database": "mydb",
    "retentionPolicy": "default",
    "points": [
        {
            "name": "cpu_load_short",
            "tags": {
                "host": "server01",
                "region": "us-west"
            },
            "timestamp": "2009-11-10T23:00:00Z",
            "fields": {
                "value": 0.64
            }
        }
    ]
}

*/
function createAnnotationBody($name, $timestamp, $tags, $text, $title, $database = null, $retentionPolicy = "default") // TODO support v0.9
{
    if ($_SESSION['is_new_influxdb_version']) {
        // TODO for the 0.9 version: add tags to payload &  test if this works once graphana supports 0.9
        return <<<FOO
    { "database" : "$database",
    "retentionPolicy" : "$retentionPolicy",
    "points" : [
        {
            "name": "$name",
            "tags": {

            },
            "timestamp": "$timestamp",
            "fields": {
                "text": "$text",
                "title": "$title"
            }
        }
    ] }
FOO;
    } else
        return <<<BAR
    [{ "name" : "$name",
    "columns" : ["time", "tags", "text", "title"],
    "points" : [      [$timestamp, "$tags", "$text", "$title"]    ]  }]
BAR;
}

function calculatePrecision($timestamp)
{
    /* "If you write data with a time you should specify the precision, which can be done via the time_precision query parameter. It can be set to either s for seconds, ms for milliseconds, or u for microseconds." */
    $length = strlen($timestamp);
    if ($length <= 10) {
        // seconds 1417651191
        return "s";
    } else if ($length <= 13) {
        // milliseconds 1417651191000
        return "ms";
    } else {
        // must be microseconds then.
        return "u";
    }
}

function getDatabaseListUrl($newVersion = null)
{
    if ($newVersion == null) $newVersion = $_SESSION['is_new_influxdb_version'];
    return ($newVersion) ? "http://" . $_SESSION['host'] . ":8086/query?q=SHOW%20DATABASES&u=" . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']) : "http://" . $_SESSION['host'] . ":8086/db?u=" . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']);
}

function getListOfDatabases()
{
    $url = getDatabaseListUrl();
    $httpResult = runHttpRequest($url);

    $result = [];

    if (200 == $httpResult['status_code']) {


        $json = json_decode($httpResult['results']);


        if ($_SESSION['is_new_influxdb_version']) {
            // debug($json->results[0]->series[0]->values);
            foreach ($json->results[0]->series[0]->values as $value) {
                //   debug($value);
                $result[] = $value[0];
            }
        } else {
            foreach ($json as $value) {
                $result[] = $value->name;
            }

        }
        sort($result);
    } else {
        debug("Error message! Maybe no database exists? Status code " . $httpResult['status_code'] . " with message " . $httpResult['results']);

    }
    return $result;
}

function runHttpRequest($url, $payload = null)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if ($payload != null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    $data = curl_exec($ch);
    $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status_code' => $statuscode, 'results' => $data];
}


function autoLimit($query)
{

    if (AUTO_LIMIT && isSelectQuery($query) && !isLimited($query)) {
        $query .= " LIMIT " . AUTO_LIMIT_VALUE;
    }
    return $query;
}

function isSelectQuery($query)
{
    return preg_match('/select .*/i', $query) > 0;
}

function isLimited($query)
{
    return preg_match('/select .* limit \d+/i', $query) > 0;
}

// deprecated
function isSeriesList($query)
{
    // return strrpos(strtolower($query), "list series") !== false;
    return preg_match('/list series.*/i', $query) > 0;
}

function getQueryType($query)
{
    if (preg_match('/list series.*/i', $query) > 0) return QueryType::v08_LIST_SERIES;
    if (strtolower($query) == "show measurements") return QueryType::v09_SHOW_MEASUREMENT;
    if (preg_match('/select .* from .*/i', $query) > 0) return $_SESSION['dbversion'] == 0.9 ? QueryType::v09_SELECT : QueryType::v08_SELECT;
}


function addCommandToCookie($command, $ts, $number_of_pages)
{
    debug("Adding query to last commands: $command");
    $cookie_name = "last_commands";
    $saveMe = $ts . DELIMITER_COMMANDCOOKIE_INTERNAL . $number_of_pages . DELIMITER_COMMANDCOOKIE_INTERNAL . $command;
    #debug("New cookie section: " . $saveMe . "<br>";
    $oldValue = readCookie($cookie_name);
    if (!cookieContainsCommand($oldValue, $command)) {
        $newValue = $oldValue . DELIMITER_COMMANDCOOKIE_EXTERNAL . $saveMe;
        #debug("Old cookie section: " . $oldValue . "<br>";
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

    foreach ($commands as $command) {
        $tokens = explode(DELIMITER_COMMANDCOOKIE_INTERNAL, $command);

        if (sizeof($tokens) == 3 && $tokens[2] == $str) {
            return true;
        }
    }

    return false;
}


function saveResultsToCache($query, $results, $timestamp, $number_of_results)
{

    if (ACTIVATE_CACHE && $number_of_results > 0) {
        $newEntry = ['timestamp' => $timestamp, 'results' => $results, 'number_of_results' => $number_of_results];
        $_SESSION['cache'][$query] = $newEntry;
        debug("Adding entry to cache for key " . $query . " with timestamp " . $timestamp . " / " . gmdate("Y-m-d\TH:i:s\Z", $timestamp));

    }
}

function searchCache($query)
{
    if (isset($_SESSION['cache'][$query]) && isFreshResult($_SESSION['cache'][$query]['timestamp'])) {
        return $_SESSION['cache'][$query];
    }
    return null;
}

function isFreshResult($timestamp)
{
    return time() - $timestamp < MAX_RESULT_AGE_CACHE_SECONDS;
}

function setPaginationWindow(&$render)
{
    $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
    if (empty($page) || !is_numeric($page) || $page < 1) {
        $page = 1;
    }
    debug("Page is " . $page);
    $start = ($page - 1) * RESULTS_PER_PAGE;

    debug("Limiting result to " . $start . " - " . ($start + RESULTS_PER_PAGE));
    $subset = array_slice($render->datapoints, $start, RESULTS_PER_PAGE);
    debug("Subset has " . sizeof($subset) . " results");

    if (!empty($subset)) {
        debug("Setting limited result");
        $render->page = $page;
        $render->datapoints = $limitedResult;
        $render->number_of_pages = ceil($render->number_of_results / RESULTS_PER_PAGE);
        $pagination_start = getPaginationStart($page, $render->number_of_pages);
        $render->start_pagination = $pagination_start;
        $render->end_pagination = getPaginationEnd($number_of_pages, $pagination_start);
    } else {
        debug("Subset creation failed, result empty");
    }
}



function getPaginationStart($page, $number_of_pages)
{
    if ($number_of_pages <= MAX_PAGINATION_PAGES) {
        debug("Pagination lower bound not limited");
        return 1;
    }
    $half = floor(MAX_PAGINATION_PAGES / 2);
    $start = $page - $half;
    debug("Pagination lower bound: $page - $half -> $start");
    return ($start < 1) ? 1 : $start;
}

function getPaginationEnd($number_of_pages, $start)
{
    if ($number_of_pages <= MAX_PAGINATION_PAGES) {
        debug("Pagination upper bound not limited");
        return $number_of_pages;
    }
    //$end = $page + ceil(MAX_PAGINATION_PAGES / 2);
    $end = $start + MAX_PAGINATION_PAGES;
    debug("Pagination upper bound: $start - " . MAX_PAGINATION_PAGES . " -> $end");
    if ($end > $number_of_pages) {
        debug("Resetting pagination upper bound to because calculated boundary $end > number of pages $number_of_pages");
        return $number_of_pages;
    } else {
        return $end;
    }
}

function debugCacheContent()
{
    if (ACTIVATE_CACHE && isset($_SESSION['cache'])) {
        foreach ($_SESSION['cache'] as $query => $record) {
            debug("Query " . $query . " with timestamp " . $record['timestamp'] . " / " . gmdate("Y-m-d\TH:i:s\Z", $record['timestamp']));
        }
    }
}

function removeOldCacheEntries()
{
    if (ACTIVATE_CACHE && isset($_SESSION['cache'])) {
        $i = 0;
        foreach ($_SESSION['cache'] as $query => $record) {
            if (!isFreshResult($record['timestamp'])) {
                $i++;
                unset($_SESSION['cache'][$query]);
                debug("Clean cache deletes query $query with timestamp " . $record['timestamp']);
            }

        }
        debug("Clean cache deleted $i entries");
    }
}

function queryCache($query, &$render)
{
    if (DEBUG) {
        debug("Content of cache at " . time() . " / " . gmdate("Y-m-d\TH:i:s\Z", time()));
        debugCacheContent();
    }

    $cache_results = searchCache($query);
    if (time() % 10 == 0) {
        // randomly remove obsolete stuff from the cache every 10th access
        removeOldCacheEntries();
    }
    if (!empty($cache_results)) {
        debug("Got data from cache. ");

        $render->datapoints = $cache_results['results']['datapoints'];
        $render->columns = $cache_results['results']['columns'];
        $render->is_cached = true;
        $render->timestamp = $cache_results['timestamp'];
        $render->number_of_results = $cache_results['number_of_results'];
        $render->number_of_pages = ceil($render->number_of_results / RESULTS_PER_PAGE);
        $render->error_message = null;
    } else {
        debug("Cache was empty.");
    }

}


function getQueryUrl($query)
{
    if ($_SESSION['is_new_influxdb_version']) {
        return "http://" . $_SESSION['host'] . ":8086/query?u="
        . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']) . "&q=" . urlencode($query) . "&db=" . urlencode($_SESSION['database']);
    } else {
        return "http://" . $_SESSION['host'] . ":8086/db/" . urlencode($_SESSION['database']) . "/series?u="
        . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']) . "&q=" . urlencode($query);
    }
}


function parseErrorMessage($httpResult, $query, &$render){
    debug("Error message! Status code: " . $httpResult['status_code'] . " for url " . $url);
    debug($httpResult['results']);
    if ($_SESSION['is_new_influxdb_version']) {
        $json = json_decode($httpResult['results']);
        # debug($json);
        $errorMessage = $json->error;
    } else {
        $errorMessage = $httpResult['results'];
    }
    $render->error_message = "Http status code " . $httpResult['status_code'] . ". Error message: " . $errorMessage;
}

function getDatabaseResults($query)
{
     # TODO if version 0.9 then warn if 0.8 query has been used, such as list series
    $render = new Renderobject();
    $render->query_type = getQueryType($query);
    $render->query = $query;

    $ignore_cache = (isset($_REQUEST['ignore_cache']) && !empty($_REQUEST['ignore_cache'])) ? $_REQUEST['ignore_cache'] == true 
                    || $_REQUEST['ignore_cache'] == "true" : false;

    if (ACTIVATE_CACHE && !$ignore_cache) 
    {
        queryCache($query, $render);
    }

    if (!$render->is_cached) 
    {
        debug("Getting data from db. ");
        $url = getQueryUrl($query);
        $httpResult = runHttpRequest($url);

        if (200 == $httpResult['status_code']) 
        {
            parseQueryResults($httpResult, $query, $render);
        } else {
            parseErrorMessage($httpResult, $query, $render);
            return $render;
        }
    }

    setPaginationWindow($render);
    return $render;
}

function parseQueryResults($httpResult, $query, &$render)
{
    $now = time();
    $render->timestamp = $now;
   
    if ($httpResult['results'] == "[]") // Series is empty
    {
        return;
    }

    debug("Response length from database: " . strlen($httpResult['results']));
    $data = json_decode($httpResult['results']);
    # debug("First 200 characters: " . substr($httpResult['results'], 0, 200));
    handle_response($data, $render);
    $render->number_of_results = count($render->datapoints);      
    debug("Got " . $render->number_of_results . " results.");  
    saveResultsToCache($query, $results, $now, $render->number_of_results); # TODO why is the number of results needed here?
    addCommandToCookie($query, $now, $number_of_pages); # TODO number of pages not known yet
    return;
}

function handle_response($data, &$render){
    $query_type = $render->query_type;
    debug("Query type for '" . $render->query . "' is " . $query_type);
    switch ($query_type) {
        case QueryType::v08_SELECT:
        case QueryType::v08_GENERIC:
            handle_v08_select($render, $data);
            break;

        case QueryType::v09_SELECT:
        case QueryType::v09_GENERIC:
            handle_v09_select($render, $data);
            break;

        case QueryType::v09_SHOW_MEASUREMENT:
            handle_v09_show_measurement($render, $data);
            break;

        case QueryType::v08_LIST_SERIES:
            handle_v08_list_series($render, $data);
            break;
        
        default:
            debug("error: unknown query type");
            # TODO handle error
            break;
    }
}

function handle_v08_list_series(&$render, $data)
{
    $render->columns = $data[0]->columns;
    $render->datapoints = $data[0]->points;    
    $render->timestamp_column = -1;
    $render->is_series_list = true;
}

function handle_v08_select(&$render, $data)
{
    $render->columns = $data[0]->columns;
    $render->datapoints = $data[0]->points;    
    $render->timestamp_column = getTimestampColumn($render->columns);    
}

function handle_v09_select(&$render, $data)
{ 
    #debug($data);
    $render->columns = $data->results[0]->series[0]->columns;
    $render->datapoints = $data->results[0]->series[0]->values;
    $render->timestamp_column = getTimestampColumn($render->columns);
}

function handle_v09_show_measurement(&$render, $data)
{ 
    $render->columns = $data->results[0]->series[0]->columns;
    $render->datapoints = $data->results[0]->series[0]->values;   
    $render->timestamp_column = -1;
    $render->is_series_list = true; 
}

# TODO test what happens when we execute list series. It should be handled here aswell.




function debug($text)
{
    if (DEBUG) {
        if (is_scalar($text)) {
            print $text;
        } else {
            print "<pre>";
            print_r($text);
            print "</pre>";
        }
        print "<br>";
    }
}


function getTimestampColumn($cols)
{
    $i = 0;
    if (!empty($cols)) {
        foreach ($cols as $name) {
            if ($name == "time") return $i;

            $i++;
        }
    }
    return -1;
}


function checkLoginValid($version = 0.8) 
{
    $url = ($version == 0.8) ? "http://" . $_POST['host'] . ":8086/db?u=" . urlencode($_POST['user']) . "&p=" . urlencode($_POST['pw']) :
        "http://" . $_POST['host'] . ":8086/query?q=SHOW%20DATABASES&u=" . urlencode($_POST['user']) . "&p=" . urlencode($_POST['pw']);
    $httpResult = runHttpRequest($url);
    debug("Login check against $url returned: ");
    debug($httpResult);
    if (200 == $httpResult['status_code']) {
        $_SESSION['dbversion'] = $version;
        $_SESSION['is_new_influxdb_version'] = $version > 0.8; // deprecated, TODO switch to version
        debug("Influx version discovered: " . $_SESSION['dbversion']);
        return true;
    }

    if ($version == 0.8) {
        // if not successful: Let's try the v0.9 version
        return checkLoginValid(0.9);
    } else {
        return false;
    }
}

function storeLoginToSession()
{
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['user'] = $_POST['user'];
    $_SESSION['pw'] = $_POST['pw'];
}


function addLoginToCookie()
{
    $cookie_name = "last_logins";
    $saveMe = $_SESSION['user'] . "@" . $_SESSION['host'];
    debug("New cookie value: " . $saveMe);
    $oldValue = readCookie($cookie_name);
    debug("Old cookie: " . $oldValue);
    if (!cookieContainsLogin($oldValue, $saveMe)) {
        $newValue = $oldValue . DELIMITER_LOGINCOOKIE_EXTERNAL . $saveMe;
        debug("Setting new cookie: " . $newValue);
        setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
    }
}

function cookieContainsLogin($oldValue, $str)
{
    $logins = explode(DELIMITER_LOGINCOOKIE_EXTERNAL, $oldValue);
    debug("Found " . sizeof($logins) . " login cookie values: ");
    foreach ($logins as $login) {
        debug($login);
        if ($login == $str) {
            debug("Login already stored");
            return true;
        }
    }

    return false;
}

abstract class QueryType {

    const v08_LIST_SERIES = 0;
    const v08_SELECT = 1;
    const v08_GENERIC = 2;
    const v09_SHOW_MEASUREMENT = 3;
    const v09_SELECT = 4;
    const v09_GENERIC = 5;  
}
