# Usage

Requires a webserver with php.
1. Clone or download the AdminfluxDB codebase
2. Install Dependencies via Composer:

        $ curl -s http://getcomposer.org/installer | php
        $ php composer.phar install


# Bugs

* in table results for list series, hide timestamp or disable link on timestamp column
* command history mit " drin funzt nicht, muss escaped werden im js
* ausgeklappt-state muss persistiert werden in einem cookie

# TODO

* extract configurable stuff into config file -> documentation
* detect timestamps and print them twice, with date format. MELANIE
* presentation
* activate & test cache
* remove obsolete classes and rearrange folder structure
* search TODOs in code :)
* remove all print statements from code
* add config option to deactivate the cache entirely (put that in doku)
* test what happens if you send an invalid query / test error message
* add support for version 0.9
* switch to HTTPS
* auto logout does not work when session variables are empty (query.php, maybe others)
* remove warning about time formatting (query template): [12:24:57] <Kay Drechsler> Found 64 results. Date of retrieval: 
Warning: date_default_timezone_get(): It is not safe to rely on the system's timezone settings. You are *required* to use the date.timezone setting or the date_default_timezone_set() function. In case you used any of those methods and you are still getting this warning, you most likely misspelled the timezone identifier. We selected the timezone 'UTC' for now, but please set date.timezone to select your timezone. in/Users/kdrechsler/Sites/Adminfluxdb/vendor/twig/twig/lib/Twig/Extension/Core.phpon line 89
2015-03-27 11:20:56.