<?php
$loader = require_once './vendor/autoload.php';

use Beibob\Blibs\Bootstrap;

if ($argc < 3) {
    print "Arguments: <module> <scriptname> [<environment>]\n";
    exit(-1);
}

$me = array_shift($argv);
$module = array_shift($argv);
$script = array_shift($argv);
$environment = isset($argv[0]) ? array_shift($argv) : "_script";
$_ENV['SERVER_NAME'] = $environment;

/**
 * Bootstrap the environment
 */
$baseDir = dirname(__DIR__);
$Bootstrap = new Bootstrap($baseDir);
loginfo("Using environment named `$environment'. Application type is `".$Bootstrap->getEnvironment()->getApplicationType()."'");

if (!preg_match("/.php$/u", $script)) {
    $script .= ".php";
}

$path = $Bootstrap->getConfig()->appDir . "/$module/scripts/$script";

if (!file_exists($path)) {
    print "Specified script file does not exist. (Path: '$path')\n";
    exit(-2);
}

// --------------------------------------------------------------------
// Utility function block
// --------------------------------------------------------------------

function logdebug($message) {
    logmessage($message, "debug");
}

function loginfo($message) {
    logmessage($message, "info");
}

function logerror($message) {
    logmessage($message, "error");
}

function logmessage($message, $level = "info") {
    print strftime("%Y-%m-%d %H:%M:%S [$level]: " . $message . "\n");
}
// --------------------------------------------------------------------
require($path);
?>