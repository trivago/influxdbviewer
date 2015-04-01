<?php

function redirectTo($path)
{
    header("Location: " . $path);
    die();
}

function sendAnnotation($timestamp, $tags, $text, $title, $name)
{
    /*
        curl -X POST -d ''  'http://10.1.3.220:8086/db/annotations/series?u=root&p=root&time_precision=s'
    */
// TODO set $_SESSION['annotation_database']
// TODO what is the name?
    $payload = createAnnotationBody($name, $timestamp, $tags, $text, $title);
    $precision = calculatePrecision($timestamp);
    $url        = "http://" . $_SESSION['host'] . ":8086/db/".$_SESSION['annotation_database']."/series?u=" . $_SESSION['user'] . "&p=" . $_SESSION['pw'] . "&time_precision=". $precision;
}

function createAnnotationBody($name, $timestamp, $tags, $text, $title){
    return <<< FOO
    [{ "name" : "$name",
    "columns" : ["time", "tags", "text", "title"],
    "points" : [      [$timestamp, "$tags", "$text", "$title"]    ]  }]
FOO
}

function calculatePrecision($timestamp){
    /* "If you write data with a time you should specify the precision, which can be done via the time_precision query parameter. It can be set to either s for seconds, ms for milliseconds, or u for microseconds." */
    $length = strlen($timestamp);
    if ($length <= 10){
        // seconds 1417651191
        return "s";
    }
    else if ($length <= 13){
        // milliseconds 1417651191000
        return "ms";
    } else {
        // must be microseconds then.
        return "u";
    } 
}

function getListOfDatabases()
{
    $url        = "http://" . $_SESSION['host'] . ":8086/db?u=" . $_SESSION['user'] . "&p=" . $_SESSION['pw'];
    $httpResult = getUrlContent($url);

    if (200 == $httpResult['status_code'])
    {

        $json   = json_decode($httpResult['results']);
        $result = array();
        foreach ($json as $value)
        {
            $result[] = $value->name;
        }
        sort($result);
        return $result;
    }
    else
    {
        debug("Error message! Maybe no database exists? Status code " . $httpResult['status_code'] . " with message " . $httpResult['results']);

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




function saveResultsToCache($query, $results, $timestamp, $number_of_results)
{
  
  if(ACTIVATE_CACHE && $number_of_results > 0 ){
   $newEntry = ['timestamp' => $timestamp, 'results' => $results, 'number_of_results' => $number_of_results];
   $_SESSION['cache'][$query] = $newEntry;
   debug("Adding entry to cache for key " . $query . " with timestamp " . $timestamp . " / " . gmdate("Y-m-d\TH:i:s\Z", $timestamp ) );
  
  }
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

function getPaginationStart($page, $number_of_pages){
   if ($number_of_pages <= MAX_PAGINATION_PAGES) {
        debug("Pagination lower bound not limited");
        return 1;
    }
    $half = floor(MAX_PAGINATION_PAGES / 2);
    $start = $page - $half;
    debug("Pagination lower bound: $page - $half -> $start");
    return ($start < 1)? 1 : $start;
}

function getPaginationEnd($page, $number_of_pages, $start){
   if ($number_of_pages <= MAX_PAGINATION_PAGES) {
        debug("Pagination upper bound not limited");
        return MAX_PAGINATION_PAGES;
    }
    //$end = $page + ceil(MAX_PAGINATION_PAGES / 2);
    $end = $start + MAX_PAGINATION_PAGES;
    debug("Pagination upper bound: $start - ".MAX_PAGINATION_PAGES." -> $end");
    if ($end > $number_of_pages){
        debug("Resetting pagination upper bound to because calculated boundary $end > number of pages $number_of_pages");
        return $number_of_pages;
    } else {
        return  $end;
    }

}

function debugCacheContent(){
   if(ACTIVATE_CACHE && isset($_SESSION['cache'])) {
    foreach($_SESSION['cache'] as $query => $record){
        debug("Query " . $query . " with timestamp " . $record['timestamp']. " / " . gmdate("Y-m-d\TH:i:s\Z", $record['timestamp']));
    }}
}

function removeOldCacheEntries(){
    if(ACTIVATE_CACHE && isset($_SESSION['cache'])) {
     foreach($_SESSION['cache'] as $query => $record){
        if (! isFreshResult($record['timestamp'])){
            unset($_SESSION['cache'][$query]);
        }
    }
  }
}

function getDatabaseResults($query)
{    
    $feedback                  = [];
    $feedback['error_message'] = null;
    $feedback['is_cached']     = false;

    $ignore_cache = (isset($_REQUEST['ignore_cache']) && !empty($_REQUEST['ignore_cache']) ) ? $_REQUEST['ignore_cache'] == true || $_REQUEST['ignore_cache'] == "true" : false;
   
    if (ACTIVATE_CACHE && !$ignore_cache)
    {
        if(DEBUG){
            debug("Content of cache at " . time() . " / " . gmdate("Y-m-d\TH:i:s\Z", time())); 
            debugCacheContent();
        }

        $cache_results = searchCache($query);
        if(time() % 10 == 0){
            // randomly remove obsolete stuff from the cache every 10th access
            removeOldCacheEntries();
        }
        if(!empty($cache_results))
        {
            debug("Got data from cache. ");
        
            $feedback['results']                 = $cache_results['results'];
            $feedback['is_cached']     = true;
            $feedback['timestamp'] = $cache_results['timestamp'];
            $feedback['number_of_results'] = $cache_results['number_of_results'];
            $feedback['number_of_pages'] = ceil($feedback['number_of_results'] / RESULTS_PER_PAGE);
           
            $feedback['error_message'] = null;
        } else {
            debug("Cache was empty.");
        }
    }
    if(!$feedback['is_cached'])
    {
        debug("Getting data from db. ");
        $now = time();
        $url = "http://" . $_SESSION['host'] . ":8086/db/" . $_SESSION['database'] . "/series?u="
            . $_SESSION['user'] . "&p=" . $_SESSION['pw'] . "&q=" . urlencode($query);

       
        $httpResult = getUrlContent($url);

        if (200 == $httpResult['status_code'])
        {
            
            if ($httpResult['results'] == "[]") // Series is empty
            {
                return  [
                'timestamp'         => $now,
                'results'           => null,
                'is_cached'         => false,
                'page'              => 1,
                'number_of_pages'   => 1,
                'number_of_results' => 0,
                'error_message'     => null
                ];
            }
            
            $json = json_decode($httpResult['results']);

            debug("Response length from database: " . strlen($httpResult['results']));
       //     debug($json);
            debug("First 200 characters: " . substr($httpResult['results'], 0, 200));
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
          
            saveResultsToCache($query, $results, $now, $number_of_results);

            addCommandToCookie($query, $now, $number_of_pages);
        }
        else
        {
        	debug("Error message! Status code: " . $httpResult['status_code'] . " for url " . $url);
        	debug ($httpResult['results']);
        	$feedback['error_message'] = "Http status code " . $httpResult['status_code'] . ". Error message: " . $httpResult['results'];
           
        }
    }
    if ($feedback['error_message'] == null)
    {
        $page          = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        debug("Page is " .$page );

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
    	if(is_scalar($text)){
    		print $text;
    	} else {
    		print_r($text);
    	}
    	print "<br>";
    }
}


function getTimestampColumn($cols){
    $i = 0;
    if(!empty($cols)) {
    foreach($cols as $name){
        if($name == "time") return $i;

        $i ++;
    }}
    return -1;
}


function checkLoginValid()
{
    $url        = "http://" . $_POST['host'] . ":8086/db?u=" . $_POST['user'] . "&p=" . $_POST['pw'];
    $httpResult = getUrlContent($url);
    return (200 == $httpResult['status_code']);
}

function storeLoginToSession()
{
    $_SESSION['host'] = $_POST['host'];
    $_SESSION['user'] = $_POST['user'];
    $_SESSION['pw']   = $_POST['pw'];
}


function addLoginToCookie()
{
    $cookie_name = "last_logins";
    $saveMe      = $_SESSION['user'] . "@" . $_SESSION['host'];
    debug("New cookie value: " . $saveMe);
    $oldValue    = readCookie($cookie_name);
    debug("Old cookie: " . $oldValue);
    if (!cookieContainsLogin($oldValue, $saveMe))
    {
        $newValue = $oldValue . DELIMITER_LOGINCOOKIE_EXTERNAL . $saveMe;
        debug("Setting new cookie: " . $newValue);
        setcookie($cookie_name, $newValue, time() + (86400 * 30), '/');
    }
}

function cookieContainsLogin($oldValue, $str)
{
    $logins = explode(DELIMITER_LOGINCOOKIE_EXTERNAL, $oldValue);
    debug("Found " . sizeof($logins) . " login cookie values: ");
    foreach ($logins as $login)
    {
        debug($login);
        if ($login == $str)
        {
            debug("Login already stored");
            return true;
        }
    }

    return false;
}


