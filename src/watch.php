<?php

include_once(__DIR__  . '/../vendor/autoload.php');

$options = getopt("", array('path:', 'mixins:'));
$path = $options['path'];
$mixinPath = $options['mixins'];

$watcher  = new ScssWatcher($path, $mixinPath);
$watcher->run();
