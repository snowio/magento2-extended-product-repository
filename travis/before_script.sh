#!/usr/bin/env bash

# create database and move db config into place
mysql -uroot -e '
    SET @@global.sql_mode = NO_ENGINE_SUBSTITUTION;
    DROP DATABASE IF EXISTS magento_integration_tests;
    CREATE DATABASE magento_integration_tests;
'

cd $HOME/build/magento2ce/dev/tests/integration
cp etc/install-config-mysql.travis.php.dist etc/install-config-mysql.php

php $TRAVIS_BUILD_DIR/travis/prepare_phpunit_config.php

if [ "$CODE_COVERAGE" = 1 ]
then
    cd ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/
    mv xdebug.ini.bak xdebug.ini
    phpenv rehash
fi
