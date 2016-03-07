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
    if (preg_match('/list series.*/i', $query) > 0) return $_SESSION['dbversion'] == 0.9 ? QueryType::v09_SELECT : QueryType::v08_SELECT;
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


function limitResult($page, $data)
{

    if (empty($page) || !is_numeric($page) || $page < 1) {
        $page = 1;
    }

    $start = ($page - 1) * RESULTS_PER_PAGE;
    debug("Limiting result to " . $start . " - " . ($start + RESULTS_PER_PAGE));
    $subset = array_slice($data, $start, RESULTS_PER_PAGE);
    debug("Subset has " . sizeof($subset) . " results");
    return $subset;
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

function queryCache($query, &$feedback)
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

        $feedback->results = $cache_results['results'];
        $feedback->is_cached = true;
        $feedback->timestamp = $cache_results['timestamp'];
        $feedback->number_of_results = $cache_results['number_of_results'];
        $feedback->number_of_pages = ceil($feedback->number_of_results / RESULTS_PER_PAGE);
        $feedback->error_message = null;
    } else {
        debug("Cache was empty.");
    }

}

function parseQueryResults($httpResult, $query)
{
    $now = time();
    $feedback = new ResultSet();

    if ($httpResult['results'] == "[]") // Series is empty
    {
        return $feedback;
    }

    $json = json_decode($httpResult['results']);

    debug("Response length from database: " . strlen($httpResult['results']));
    //     debug($json);
    # debug("First 200 characters: " . substr($httpResult['results'], 0, 200));

    if ($_SESSION['is_new_influxdb_version']) {
        debug($json->results);
        $columns = null;
    } else {
        $columns = $json[0]->columns;
        $datapoints = $json[0]->points;
    }
    $results = ['columns' => $columns, 'datapoints' => $datapoints];
    $number_of_results = count($datapoints);
    $number_of_pages = ceil($number_of_results / RESULTS_PER_PAGE);
    debug("Got " . $number_of_results . " results.");
    $feedback->results = $results;
    $feedback->number_of_pages = $number_of_pages;
    $feedback->number_of_results = $number_of_results;

    saveResultsToCache($query, $results, $now, $number_of_results);
    addCommandToCookie($query, $now, $number_of_pages);
    return $feedback;
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

function getDatabaseResults($query) // TODO add support for 0.9
{
    $feedback = new ResultSet();

    $ignore_cache = (isset($_REQUEST['ignore_cache']) && !empty($_REQUEST['ignore_cache'])) ? $_REQUEST['ignore_cache'] == true || $_REQUEST['ignore_cache'] == "true" : false;

    if (ACTIVATE_CACHE && !$ignore_cache) {
        queryCache($query, $feedback);
    }
    if (!$feedback->is_cached) {
        debug("Getting data from db. ");
        $url = getQueryUrl($query);
        $httpResult = runHttpRequest($url);

        if (200 == $httpResult['status_code']) {
            $feedback = parseQueryResults($httpResult, $query);
        } else {
            debug("Error message! Status code: " . $httpResult['status_code'] . " for url " . $url);
            debug($httpResult['results']);
            if ($_SESSION['is_new_influxdb_version']) {
                $json = json_decode($httpResult['results']);
                # debug($json);
                $errorMessage = $json->error;
            } else {
                $errorMessage = $httpResult['results'];
            }
            $feedback->error_message = "Http status code " . $httpResult['status_code'] . ". Error message: " . $errorMessage;
            # TODO if version 0.9 then warn if 0.8 query has been used, such as list series
            return $feedback;

        }
    }
    if ($feedback->error_message == null) {
        setPaginationWindow($feedback);
    }

    return $feedback;
}

function setPaginationWindow(&$feedback)
{
    $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
    debug("Page is " . $page);

    $limitedResult = limitResult($page, $feedback->results['datapoints']);

    if (!empty($limitedResult)) {
        debug("Setting limited result");
        $feedback->page = $page;
        $feedback->results['datapoints'] = $limitedResult;
    } else {
        debug("Subset creation failed, result empty");
    }
}

function debug($text)
{
    if (DEBUG) {
        if (is_scalar($text)) {
            print $text;
        } else {
            print_r($text);
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
    const v09_SHOW_MEASUREMENT = 2;
    const v09_SELECT = 3; 
}
