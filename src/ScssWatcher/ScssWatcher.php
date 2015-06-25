<?php

namespace ScssWatcher;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \RegexIterator;
use \SplFileInfo;

/**
 * Recursively monitors a directory for changed `.scss` files and generates `.css` versions
 * 
 * Usage (see `scripts/watch.php'):
 *      $watcher = new ScssWatcher($path);
 *      $watcher->run();
 * 
 * Defaults:
 *      style: `expanded` (can be changed via `$watcher->style`)
 *      flags: `--sourcemap` (can be changed via `$watcher->flags`)
 * 
 * @author Benjamin Nowack
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */
class ScssWatcher {
    
    /**
     * @var string The entry/root directory to monitor 
     */
    protected $entryPath = null;
    
    /**
     * @var int Modification timestamp of mixins or other system SCSS files with a leading `_`
     */
    protected $sysMod = 0;
    
    /**
     * @var int Oldest modification timestamp of all CSS files
     */
    protected $oldestMod = 0;
    
    /**
     * @var string Current error string returned by sass binary
     */
    protected $currentError = null;
    
    /**
     * @var int Interval in seconds for re-scanning the directory 
     */
    public $delay = 1;
    
    /**
     * @var string Path to sass binary 
     */
    public $sassBinary = '/usr/bin/sass';
    
    /**
     * @var string CSS formatting style
     */
    public $style = 'expanded';
    
    /**
     * @var string Additional sass binary flags 
     */
    public $flags = '--sourcemap';
    
    /**
     * Instantiates the class with a root/entry directory
     * @param string $entryPath Path to entry directory
     */
	public function __construct($entryPath) {
        $this->entryPath = realpath($entryPath);
        $this->oldestMod = time();
        $this->sassBinary = $this->getBinPath() ?: $this->sassBinary;
        echo "Watching '" . $this->entryPath . "' with subdirectories\n";
	}
    
    /**
     * Returns the path to an installed sass binary
     * 
     * @return string Path to sass binary
     */
    protected function getBinPath()
    {
        return trim(shell_exec("which sass 2>&1"));
    }
    
    /**
     * Starts the watcher
     */
    public function run() {
        while (true) {
            $this->checkFiles();
            sleep($this->delay);
        }
    }
    
    /**
     * Returns a list of all `.scss` files in the entry directory
     * 
     * @return RegexIterator List of files
     */
    protected function getFiles() {
        $dir_iterator = new RecursiveDirectoryIterator($this->entryPath);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);
        $files = new RegexIterator($iterator, '/^.+\.scss$/i');
        return $files;
    }
    
    /**
     * Checks all `.scss` files for modifications
     * 
     * @param bool $force Whether to process files independently of any modifications
     */
    protected function checkFiles($force = false) {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $this->checkFile($file, $force);
        }
    }
    
    /**
     * Checks a given file for modifications and triggers CSS generation
     * 
     * @param SplFileInfo $file A file
     * @param bool $force Whether to process file independently of any modifications
     */
    protected function checkFile(SplFileInfo $file, $force) {
        $scssPath = $file->getPathname();
        $scssMod = $file->getMTime();
        $scssName = preg_replace('/^.+\/([^\/]+)$/', '\\1', $scssPath);

        // don't compile `_`-prefixed system files
        if (substr($scssName, 0, 1) === '_') {
            $this->sysMod = max($this->sysMod, $scssMod);
            return;
        }

        $cssPath = preg_replace('/(\.|\/)scss($|\/)/', '\\1css\\2', $scssPath);
        $cssMod = is_file($cssPath) ? filemtime($cssPath) : 0;
        $this->oldestMod = min($this->oldestMod, $cssMod);

        $isModified = $scssMod - $cssMod > 0 ? true : false;

        if ($isModified || $force) {
            $this->generateCss($scssPath, $cssPath);
        }
        
        // Possibly imported mixin/sys file changed, update all CSS files
        if (!$force && $this->sysMod > $this->oldestMod) {
            $this->checkFiles(true);
            $this->oldestMod = $this->sysMod;
        }        
    }
    
    /**
     * Converts a `.scss` file to the corresponding `.css` version
     * 
     * @param string $scssPath Path to SCSS file
     * @param string $cssPath Target path for the CSS file
     */
    protected function generateCss($scssPath, $cssPath) {
        $cssDirName = preg_replace('/^(.+)\/[^\/]+$/', '\\1', $cssPath);
        if (!is_dir($cssDirName)) {
            mkdir($cssDirName, 0777);
        }
        $response = trim(shell_exec("$this->sassBinary $scssPath $cssPath --style $this->style $this->flags 2>&1"));
        if (strpos($response, 'error') !== false) {
            if ($response === $this->currentError) {
                echo '.';
            } else {
                echo substr($scssPath, strlen($this->entryPath)) . "\n" . $response . "\n";
            }
            $this->currentError = $response;
        }
        else {
            if ($this->currentError) {
                echo "fixed\n";
                $this->currentError = null;
            }
            echo substr($scssPath, strlen($this->entryPath)) . "\n";
        }
    }
    
}
