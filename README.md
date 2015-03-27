# Usage

Requires a webserver with php.
1. Clone or download the AdminfluxDB codebase
2. Install Dependencies via Composer:

        $ curl -s http://getcomposer.org/installer | php
        $ php composer.phar install


# Bugs

* cookie setting on front page does not work. Unclear if on server or client side. 
* css class for highlighting the active page is not working/implemented
* Logo missing in title (various templates)
* no animations yet when sending query or logging in


# TODO

* detect timestamps and print them twice, with date format.
* presentation
* activate & test cache
* remove obsolete classes and rearrange folder structure
* add links to results if is a "list series" result
* add new logo if we get one
* search TODOs in code :)
* remove all print statements from code
* add config option to deactivate the cache entirely (put that in doku)