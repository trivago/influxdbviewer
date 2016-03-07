<?php
require("config.inc.php");
require("func.inc.php");
require("renderobject.class.php");

require __DIR__ . '/vendor/autoload.php';
session_start();

if (!isset($_SESSION['host']) || empty($_SESSION['host']) || !isset($_SESSION['user']) || empty($_SESSION['user']) || !isset($_SESSION['pw']) || empty($_SESSION['pw']))
{
    redirectTo("index.php");
}
if (!isset($_SESSION['database']) || empty($_SESSION['database']))
{
    redirectTo("databases.php");
}

define('DELIMITER_COMMANDCOOKIE_INTERNAL', "#");
define('DELIMITER_COMMANDCOOKIE_EXTERNAL', "|");

try
{
    // specify where to look for templates
    $loader = new Twig_Loader_Filesystem('templates');

    // initialize Twig environment
    $twig = new Twig_Environment($loader);

    // load template
    $template = $twig->loadTemplate('query.twig');

    $render = new Renderobject();
    
    if (!empty($_REQUEST['query']))
    {
        $query = $_REQUEST['query'];
        $query = autoLimit($query);
        $render->query = $query;
        $feedback = getDatabaseResults($query);       
        handle_response($feedback, $render);
    }

    echo $template->render(
        (array) $render
    );
} 
catch (Exception $e)
{
    die ('ERROR: ' . $e->getMessage());
}

function handle_v08_select($render, $feedback){
    $render->datapoints = $feedback->results['datapoints'];
    $render->timestamp = $feedback->timestamp;
    $render->is_cached = $feedback->is_cached;
    $render->page = $feedback->page;
    $render->is_series_list = isSeriesList($query);
    $render->number_of_pages = $feedback->number_of_pages;
    $render->number_of_results = $feedback->number_of_results;
    $render->error_message = $feedback->error_message;
    $pagination_start = getPaginationStart($page, $render->number_of_pages);
    $render->start_pagination = $pagination_start;
    $render->end_pagination = getPaginationEnd($number_of_pages, $pagination_start);
    $render->timestamp_column = getTimestampColumn($feedback->results['columns']);
}

function handle_v09_select($render, $feedback){ 
    # TODO
    debug($feedback->results['measurements']);
    $render->datapoints = $feedback->results['measurements'];
    $render->timestamp = $feedback->timestamp;
    $render->is_cached = $feedback->is_cached;
    $render->page = $feedback->page;
    $render->is_series_list = isSeriesList($query);
    $render->number_of_pages = $feedback->number_of_pages;
    $render->number_of_results = $feedback->number_of_results;
    $render->error_message = $feedback->error_message;
    $pagination_start = getPaginationStart($page, $render->number_of_pages);
    $render->start_pagination = $pagination_start;
    $render->end_pagination = getPaginationEnd($number_of_pages, $pagination_start);
    $render->timestamp_column = getTimestampColumn($feedback->results['columns']);
}

function handle_v09_show_measurement($render, $feedback){ 
    # TODO
    $render->datapoints = $feedback->results['datapoints'];
    $render->timestamp = $feedback->timestamp;
    $render->is_cached = $feedback->is_cached;
    $render->page = $feedback->page;
    $render->is_series_list = isSeriesList($query);
    $render->number_of_pages = $feedback->number_of_pages;
    $render->number_of_results = $feedback->number_of_results;
    $render->error_message = $feedback->error_message;
    $pagination_start = getPaginationStart($page, $render->number_of_pages);
    $render->start_pagination = $pagination_start;
    $render->end_pagination = getPaginationEnd($number_of_pages, $pagination_start);
    $render->timestamp_column = getTimestampColumn($feedback->results['columns']);
}


function handle_response($feedback, &$render){
    $query_type = getQueryType($render->query);
    switch ($query_type) {
        case QueryType::v08_SELECT:
            handle_v08_select($render, $feedback);
            break;

        case QueryType::v09_SELECT:
            handle_v09_select($render, $feedback);
            break;

        case QueryType::v09_SHOW_MEASUREMENT:
            handle_v09_show_measurement($render, $feedback);
            break;
        
        default:
            # code...
            break;
    }
}

