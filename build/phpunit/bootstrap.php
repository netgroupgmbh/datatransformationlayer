<?php
/**
 * @author      pfroch <info@netgroup.de>
 * @link        http://www.netgroup.de
 * @copyright   NetGroup 2014
 * @license     EULA
 * @since       01.08.24 - 10:00
 */

/**
 * include esit_contaoTestCase
 */
if (!defined('__DIR__') || empty(__DIR__)) {
    define('__DIR__', realpath(__FILE__));
}

$buildDir       = __DIR__ . '/..';
$rootDir        = __DIR__ . '/../..';
$namesapce      = 'NetGroup';
$testCase       = __DIR__ . '/NetGroupTestCase.php';

if (substr_count(__DIR__, "/src/$namesapce/")) {
    $arrPaths = explode("/src/$namesapce/", __DIR__);
} elseif (substr_count(__DIR__, '/vendor/')) {
    $arrPaths = explode('/vendor/', __DIR__);
} else {
    $arrPaths = explode('/build/phpunit', __DIR__);
}

if (is_array($arrPaths)) {
    define('CONTAO_ROOT', $arrPaths[0]);
} else {
    define('CONTAO_ROOT', '');
}

$globalComposerAutoloadPath = CONTAO_ROOT . '/vendor/autoload.php';    // Wird während der Entwicklung verwendet

if (\is_file($globalComposerAutoloadPath)) {
    // Globalen Composer Autoload einbinden
    include_once($globalComposerAutoloadPath);
    $autoloadFound = true;
} else {
    throw new \Exception("No autoload found");
}

if (\is_file($testCase)) {
    include_once($testCase);
} else {
    throw new \Exception('Testcase is missing: ' . $testCase);
}
