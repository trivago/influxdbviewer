# Usage

1. Clone or download the AdminfluxDB codebase
2. Install Dependencies via Composer:

        $ curl -s http://getcomposer.org/installer | php
        $ php composer.phar install
3. Install apache and php, also php5-curl and php5-intl


# Bugs

* slideout must not disappear when we want to click a link (currently deactivated). It must react only to a click on the image.
* cookie setting on front page does not work. Unclear if on server or client side. 


# TODO

* test pagination
* test cache
* remove obsolete classes and rearrange folder structure
* add links to results if is a "list series" result
* display date of retrieval and cache-flag
* add pagination to template
* add new logo