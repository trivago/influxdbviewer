<?php

class Renderobject extends Resultset { 
# TODO find out why this is different than Resultset and merge or extend
	
	var $title = 'Results'; 
    var $user = $_SESSION['user'];
    var $host = $_SESSION['host'];
    var $database = $_SESSION['database'];
    var $results = null;
	var $datapoints = [];
    var $columns = [];
    var $is_series_list = false; // TODO switch to enum
    var $start_pagination = 1;
    var $end_pagination = 1;
    var $timestamp_column = "";
}
?>