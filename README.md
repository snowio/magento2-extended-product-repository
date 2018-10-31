# Magento 2 Extended Product Repository

[![Build Status](https://travis-ci.org/snowio/magento2-extended-product-repository.svg?branch=master)](https://travis-ci.org/snowio/magento2-extended-product-repository)
[![codecov](https://codecov.io/gh/snowio/magento2-extended-product-repository/branch/master/graph/badge.svg)](https://codecov.io/gh/snowio/magento2-extended-product-repository)

## Description
A Magento 2 module that adds the following extension attributes.
* **Attribute code field in configurable product options**: 
Endpoints can now specify an attribute code for a configurable product option.
* **Configurable product linked skus**:
Endpoints can now specify product skus as configurable product links

## Prerequisites
* PHP 7.0 or newer
* Composer  (https://getcomposer.org/download/).
* `magento/framework` 100 or newer
* `magento/module-catalog` 101 or newer
* `magento/module-eav` 100 or newer
* `magento/module-configurable-product` 100 or newer


## Installation
```
composer require snowio/magento2-extended-product-repository
php bin/magento module:enable SnowIO_ExtendedProductRepository
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Usage
* A **configurable_product_option** can now have an `attribute_code` that enables the endpoint specify the attribute code for a configurable product option.
* **configurable_product_linked_skus** allows the endpoint to specify sku's as configurable product links instead of magento ID's.
```json
{
    "product": {
        "type_id": "configurable",
        "sku": "test-from-snowio-configurable",
        "attribute_set_id": 11,
        "name": "test from snow.io configurable",
        "price": 10,
        "visibility": 4,
        "status": 1,
        "custom_attributes": {
            "axis_size": "s_xxl",
            "url_key": "test-from-snowio-configurable.html"
        },
        "extension_attributes": {
            "configurable_product_options": [
                {
                    "extension_attributes": {
                        "attribute_code": "axis_size"
                    }
                }
            ],
            "configurable_product_linked_skus": [
                "test-from-snowio-simple"
            ]
        }
    }
}
```
## License
This software is licensed under the MIT License. [View the license](LICENSE)
