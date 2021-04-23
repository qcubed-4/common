<?php

$loader = require dirname(dirname(dirname(dirname(__FILE__)))) . '/autoload.php'; // Add the Composer autoloader if using Composer

// During development, these are not yet in the composer autoloader cache, so add them here
$strPackagePath =  dirname(__DIR__);
$loader->addPsr4('QCubed\\', $strPackagePath . '/src');

define ('QCUBED_ENCODING', 'UTF-8');

