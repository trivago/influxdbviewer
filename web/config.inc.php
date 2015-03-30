<?
define('VENDOR_PATH', '../vendor/');
echo VENDOR_PATH;
exit();

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
?>