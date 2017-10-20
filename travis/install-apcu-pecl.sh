#!/bin/bash

if [[ "$(php -v | grep 'PHP 7.0')" ]] ; then
    yes | pecl -f install channel://pecl.php.net/APCu-5.1.8;
elif [[ "$(php -v | grep 'PHP 7.1')" ]] ; then
    yes | pecl -f install channel://pecl.php.net/APCu-5.1.8;
else
    yes | pecl -f install channel://pecl.php.net/APCu-4.0.7;
fi

php -m | grep apc