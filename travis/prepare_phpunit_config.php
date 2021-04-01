<?php
$magentoPath = getcwd();
if (isset($argv[1])) {
    $suggestedPath = realpath($argv[1]);
    if ($suggestedPath) {
        $magentoPath = $suggestedPath;
    }
}

if (!is_file($magentoPath . '/app/etc/di.xml')) {
    throw new \Exception('Could not detect magento root: ' . $magentoPath);
}

$configPath = "$magentoPath/dev/tests/integration/phpunit.xml.dist";
$travisBuildDir = realpath(__DIR__ . '/../');
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
