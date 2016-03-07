<?php

class Renderobject { 
	var $is_cached = false;
	var $error_message = "";
	var $results = null;
	var $page = 1;
    var $number_of_pages = 0;
    var $number_of_results = 0;
    
    var $query_type = 0;
	var $title = 'Results'; 
    var $user = $_SESSION['user'];
    var $host = $_SESSION['host'];
    var $database = $_SESSION['database'];
    var $datapoints = [];
    var $columns = [];
    var $is_series_list = false; // TODO switch to enum
    var $start_pagination = 1;
    var $end_pagination = 1;
    var $timestamp_column = -1;
    var $timestamp = 0;
}
?>