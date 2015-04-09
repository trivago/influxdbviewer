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
* click on the three dots on the left of the screen to expand your query history.

# Dependencies

* twig >= 1.8

# IMPROVEMENTS & FEATURE SUGGESTIONS
* add support for version 0.9 => coming very soon
* maybe add auto completion for queries?
* add date/time picker to script for adding annotations
* when selecting a query from the history: exchange text in result state box to "not run yet, invalid results" or something
* show annotations which are already in the database
* annotation template: display  message and error in different colours