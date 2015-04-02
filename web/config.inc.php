<?php
define('VENDOR_PATH', '../vendor/');

define("DEBUG",false);

// If you often switch between resultsets, you might want to activate the cache so that recently polled data is re-used, without querying the database again.
define('ACTIVATE_CACHE', true);
// if the cache is activated: how long do you want to store results in there?
define('MAX_RESULT_AGE_CACHE_SECONDS', 30);


// this defines the pagination windows:
define('RESULTS_PER_PAGE', 30);

// would you like the tool to add a "limit 1000" clause to every select statement which does not contain a limit already?
define("AUTO_LIMIT", true);
// the value for the auto-limit clause, if activated:
define("AUTO_LIMIT_VALUE", 1000);

define('MAX_PAGINATION_PAGES', 10);

// if you want to send annotations to the database and you dont want to have to select the database name every time: set it here.
define(DEFAULT_ANNOTATION_DATABASE, "events");
