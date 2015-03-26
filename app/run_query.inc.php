<?

if(!empty($_REQUEST['query']){
	$feedback =  getDatabaseResults();
	$results = $feedback['result'];
	$timestamp = $feedback['timestamp'];
	$is_cached = $feedback['is_cached'];

	$query = $_REQUEST['query'];
	$is_series_list = isSeriesList($query);
	saveResultsToCache($query, $results, mktime());
	}
	
function getDatabaseResults($query){
	$cache_results = search_cache($query);
	if ($cache_results != null && !$_REQUEST['ignore_cache'){
		{
			$cache_results['is_cached'] = true;

			return $cache_results;
		}
	$url = "http://" + $_SESSION['host'] + "/db/" + $_SESSION['database'] + "/series?u=" + $_SESSION['user'] + "&p=" + $_SESSION['pw'] + "&q=" + urlencode($query);
	$results = file_get_contents($url);
	$json = json_decode($results);
	print_r($json);
	// TODO set error message if it contains any
	}

function isSeriesList($query){
	$i = strrpos(strtolower($query), "list series");
	return $i !== FALSE;
}


function saveResultsToCache($query, $results, $timestamp){
	// TODO implement
	
}

function search_cache($query){
	return null; // TODO implement
}