<?php
$magentoPath = \getenv('HOME') . '/build/magento2ce';
$configPath = "$magentoPath/dev/tests/integration/phpunit.xml.dist";
$travisBuildDir = \getenv('TRAVIS_BUILD_DIR');
$packageName = \exec("composer config name -d $travisBuildDir");

$config = new \SimpleXMLElement($configPath, 0, true);

unset($config->testsuites);
$testsuiteNode = $config->addChild('testsuites')->addChild('testsuite');
$testsuiteNode->addAttribute('name', 'Integration');
$testsuiteNode->addChild('directory', "$travisBuildDir/Test/Integration")->addAttribute('suffix', 'Test.php');

$codeCoverage = \getenv('CODE_COVERAGE');
unset($config->logging);
if ($codeCoverage) {
    $logNode = $config->addChild('logging')->addChild('log');
    $logNode->addAttribute('type', 'coverage-clover');
    $logNode->addAttribute('target', "$travisBuildDir/coverage.xml");

    unset($config->filter);
    $whitelistNode = $config->addChild('filter')->addChild('whitelist');
    $whitelistNode->addChild('directory', "../../../vendor/$packageName")->addAttribute('suffix', '.php');
    $whitelistNode->addChild('exclude')->addChild('file', "../../../vendor/$packageName/registration.php");
    $whitelistNode->addChild('exclude')->addChild('directory', "../../../vendor/$packageName/Setup");
    $whitelistNode->addChild('exclude')->addChild('directory', "../../../vendor/$packageName/Test");
    $whitelistNode->addChild('exclude')->addChild('directory', "../../../vendor/$packageName/travis");
}

$config->asXML($configPath);
