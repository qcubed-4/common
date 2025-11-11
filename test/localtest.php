<?php

$_SESSION['HtmlReporterOutput'] = '';

$cliOptions = [ 'phpunit'];	// first entry is the command
array_push($cliOptions, '-c', __DIR__ . '/phpunit-local.xml');	// the config file is here

require dirname(dirname(dirname(dirname(__FILE__)))) . '/autoload.php'; // Find PHPUnit\TextUI\sCommand

$tester = new PHPUnit\TextUI\Command();

$tester->run($cliOptions);
