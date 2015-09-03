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

$options = getopt("", array('path:', 'bin:'));
$path = $options['path'];
$bin = isset($options['bin']) ? $options['bin'] : null;

$watcher = new ScssWatcher($path, $bin);
$watcher->run();
