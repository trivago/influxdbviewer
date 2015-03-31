# Purpose

This tool allows easier access to influxdb databases. It offers pagination, caching, command and login history and browser back button functionality. Also adds LIMIT clauses to unlimited queries, for safety. Both the auto-limit and the cache can be deactivated in the config.

# Usage

Requires a webserver with php.
1. Clone or download the AdminfluxDB codebase
2. Install Dependencies via Composer:
        $ curl -s http://getcomposer.org/installer | php
        $ php composer.phar install
3. Set date.timezone option in php.ini because otherwise the twig template engine might complain.
4. Configure your webserver to include this project, and please use https because the credentials are sent to the database without being encrypted.
5. Have fun!

# Tips
* to see a human readable version of any timestamp, move the mouse over it.
* after running a list series command, click on the series name to get the latest 1000 entries for this metric.

# Dependencies

* twig >= 1.8

# Bugs

* add dotted link to abbr tags
* need to remove "undefined" entries from login screen
* Bug im Caching
* search TODOs in code :)
* remove warning about time formatting (query template): [12:24:57] <Kay Drechsler> Found 64 results. Date of retrieval: 
Warning: date_default_timezone_get(): It is not safe to rely on the system's timezone settings. You are *required* to use the date.timezone setting or the date_default_timezone_set() function. In case you used any of those methods and you are still getting this warning, you most likely misspelled the timezone identifier. We selected the timezone 'UTC' for now, but please set date.timezone to select your timezone. in/Users/kdrechsler/Sites/Adminfluxdb/vendor/twig/twig/lib/Twig/Extension/Core.phpon line 89
2015-03-27 11:20:56.


# FEATURE SUGGESTIONS
* add support for version 0.9 coming very soon
* maybe add auto completion for queries? It was suggested to look into select2 jquery
