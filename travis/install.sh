#!/usr/bin/env bash

PACKAGE_NAME=`composer config name`
cd $HOME/build
git clone --branch $MAGENTO_VERSION https://github.com/magento/magento2.git magento2ce --depth=1
cd magento2ce
composer install --ignore-platform-reqs
composer config minimum-stability dev
composer require $PACKAGE_NAME:* --no-update --ignore-platform-reqs
composer update $PACKAGE_NAME --ignore-platform-reqs
