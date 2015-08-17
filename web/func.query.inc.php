<?php


function remove_time_column(&$data){
    $htd = has_time_data($data);
    debug("Result has time data: " . $htd);
    // make sure that we dont remove the time column twice, because we will work based on the position, without checking what we delete:
    if($htd){ 
        unset($data['results']['columns'][0]);
        for($i = 0; $i < sizeof($data['results']['datapoints']); $i++){
            /***
            This method is used for 'list series' results only, so we can guarantee that the order of columns is time, value.
            ***/
            unset($data['results']['datapoints'][$i][0]);
        }
    } 

}

function has_time_data(&$data){
    return in_array("time", $data['results']['columns']);
}

function addTimelinessColumn(&$data){
    $data['results']['columns'][] = ""; // empty on purpose. This column will contain the liveness-colourflag
    $data['results']['columns'][] = "Last updated";
    for($i = 0; $i < sizeof($data['results']['datapoints']); $i++){
        $data['results']['datapoints'][$i] = addTimelinessToDatapoint($data['results']['datapoints'][$i]);
    }
}

function getLastUpdate($metricname){
    $query = "select * from \"" .$metricname . "\" order desc limit 1";
    $url = "http://" . $_SESSION['host'] . ":8086/db/" . $_SESSION['database'] . "/series?u="
            . urlencode($_SESSION['user']) . "&p=" . urlencode($_SESSION['pw']) . "&q=" . urlencode($query);

    $httpResult = getUrlContent($url);

    if (200 == $httpResult['status_code']) {

        if ($httpResult['results'] == "[]") // Series is empty
        {
            debug("series has no results");
           return -1;
        }

        $json = json_decode($httpResult['results']);

        $datapoints = $json[0]->points;

        // debug("Last update for metric " . $metricname . " was on " .$datapoints[0][0]); 

        return $datapoints[0][0];
        
    } 
    else {
        debug("Error message! Status code: " . $httpResult['status_code'] . " for url " . $url);
        debug($httpResult['results']);
       return -1;
    }
    return -1;
}

function addTimelinessToDatapoint($datapoint){
   
    
    $metricname = $datapoint[1]; // structure is like this: Array ( [0] => some time value [1] => metric name ) 

     # Look into cache:
    if(ACTIVATE_CACHE && isset($_SESSION['timeliness_cache']) && in_array($metricname, $_SESSION['timeliness_cache'])){
        $entry = $_SESSION['timeliness_cache'][$metricname];
        debug("Found entry in cache for metric " . $metricname ." with value " .$entry['value']." and cache time " . $entry['time'] ); // TODO cache does not work yet.
        if(isFreshResult($entry['time'])){
            debug("entry was fresh");
            return _appendTimeComment($datapoint, $entry['value']);
        }
    }
    $timestamp = getLastUpdate($metricname);
    if(ACTIVATE_CACHE){
        $entry = array("value"=>$timestamp, "time" => time());
        $_SESSION['timeliness_cache'][$metricname] = $entry;  
        // debug("New entry in timecache: ");
        // debug($entry);
    }
    
    return _appendTimeComment($datapoint, $timestamp);
}

function getTimeDifferenceColour($timestamp){
    $now = time()*1000;
    $delta = $now - $timestamp;
    
    // debug("Current time " . $now . " - " . $timestamp . " = " . $delta);
    
    if ($delta < TIMELINESS_LIMIT_GREEN)  {
        return "green";
    } 
    else if($delta < TIMELINESS_LIMIT_YELLOW){
        return "yellow";
    }
    else if($delta < TIMELINESS_LIMIT_ORANGE){
        return "orange";
    }
    else return "grey";

}

function _appendTimeComment($datapoint , $timestamp){
    // incoming structure is like this: Array ( [0] => some time value [1] => metric name ) 
    // outgoing structure is like this: Array ( [0] => some time value [1] => metric name [2] => image name for colour [3] => timestamp) 
    $datapoint[] = getTimeDifferenceColour($timestamp);
    $datapoint[] = $timestamp;
    // debug($datapoint);
    return $datapoint;
}




