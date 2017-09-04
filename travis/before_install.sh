#!/usr/bin/env bash

cd ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d
mv xdebug.ini xdebug.ini.bak
echo 'memory_limit = -1' >> travis.ini
phpenv rehash

composer config --global repo.packagist false
composer config --global repo.package path $TRAVIS_BUILD_DIR
