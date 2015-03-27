# Usage

Requires a webserver with php.
1. Clone or download the AdminfluxDB codebase
2. Install Dependencies via Composer:

        $ curl -s http://getcomposer.org/installer | php
        $ php composer.phar install


# Bugs

* slideout must not disappear when we want to click a link (currently deactivated). It must react only to a click on the image.
* cookie setting on front page does not work. Unclear if on server or client side. 
* database list is not in table
* query results table is not at the right position
* query input field needs styling
* list of last commands needs to be wider
* when no cookie for last commands is stored, it says undefined
* css class for highlighting the active page is not working/implemented
* Logo missing in title (various templates)


# TODO

* presentation
* test cache
* remove obsolete classes and rearrange folder structure
* add links to results if is a "list series" result
* add new logo if we get one
* search TODOs in code :)
* remove all print statements from code