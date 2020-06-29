<?php

use Beibob\Blibs\Bootstrap;
use Beibob\Blibs\Environment;

require_once 'vendor/autoload.php';

/**
 * Bootstrap the environment
 */
Environment::getInstance('_script.local');
new Bootstrap(__DIR__);
