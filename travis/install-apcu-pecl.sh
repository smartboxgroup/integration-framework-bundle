#!/bin/bash

if [[ $TRAVIS_PHP_VERSION = 7.* ]]; then
    yes | pecl install channel://pecl.php.net/APCu-5.1.8;
else
    yes | pecl install channel://pecl.php.net/APCu-4.0.11;
fi

php -m | grep apc