<?php

class Renderobject extends Resultset { 

	var $title = 'Results'; 
    #var $user = $_SESSION['user']; // TODO fix
    #var $host = $_SESSION['host']; // TODO fix
    #var $database = $_SESSION['database']; // TODO fix
    var $results = null;
	var $datapoints = [];
    var $columns = [];
    var $is_series_list = false; // TODO switch to enum
    var $start_pagination = 1;
    var $end_pagination = 1;
    var $timestamp_column = "";
}
?>