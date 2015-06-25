<?php

/**
 * Command line script for the ScssWatcher class
 * 
 * Usage:
 *      php scripts/watch.php --path=/path/to/entry/directory
 * 
 */
include_once(__DIR__  . '/../vendor/autoload.php');

use \ScssWatcher\ScssWatcher;

$options = getopt("", array('path:'));
$path = $options['path'];

$watcher = new ScssWatcher($path);
$watcher->run();
