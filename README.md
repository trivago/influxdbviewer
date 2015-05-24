# Purpose

This tool allows easier access to influx db databases. It offers pagination, caching, command and log history and browser back button functionality. Also adds LIMIT clauses to unlimited queries, for safety. Both the utility and the cache can be deactivated in the configure.

![Query result screenshot](documentation/screenshot1.png)

# Usage

Requires a web server with PP.

1. Clone or download the InfluxDBviewer co debase
2. Install Dependencies via Composer:  
    `$ curl -s http://getcomposer.org/installer | php`  
    `$ php composer.phar install`
3. Set date.timezone option in php.ini because otherwise the twig template engine might complain.
4. Configure your webserver to include this project, and please use https because the credentials are sent to the database without being encrypted.
5. Have fun!

# Tips
* to see a human readable version of any timestamp, move the mouse over it.
* after running a list series command, click on the series name to get the latest 1000 entries for this metric.
* click on the three dots on the left of the screen to expand your query history.

# Dependencies

* twig >= 1.8

# Feature suggestions
* add support for version 0.9 => coming very soon
* display series name per datapoint if the select statement used a regex.

# Acknowledgements

Melanie Patrick, Kay Drechsler, Christoph Reinartz, Matthias Cieschinger, Matthias Endler, Inga Feick @ trivago GmbH
