<?php
namespace SnowIO\ExtendedProductRepository\Test;

if (\class_exists(\PHPUnit\Framework\TestCase::class)) {
    abstract class TestCase extends \PHPUnit\Framework\TestCase
    {

    }
} else {
    abstract class TestCase extends \PHPUnit_Framework_TestCase
    {

    }
}