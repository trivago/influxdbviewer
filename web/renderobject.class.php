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
    
    var $datapoints = [];
    var $columns = [];
    var $is_series_list = false; // TODO switch to enum
    var $start_pagination = 1;
    var $end_pagination = 1;
    var $timestamp_column = -1;
    var $timestamp = 0;

    /**
We need this data in the template and setting it here makes the access easiest. We cannot set these data in the class definition because
it must not contain runtime dependant variables in the member initialization. See here: 
*/
    public function __construct() 
    {
        $this->user = $_SESSION['user'];
        $this->host = $_SESSION['host'];
        $this->database = $_SESSION['database'];
    }
}
?>