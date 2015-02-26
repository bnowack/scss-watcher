<?php

use \Leafo\ScssPhp\Compiler AS Compiler;

class ScssWatcher {
    
    protected $path = null;
    protected $sysMod = 0;
    protected $oldestMod = 0;
    
    public $mixinPath = null;
    public $formatter = '\Leafo\ScssPhp\Formatter\Nested';
    
	public function __construct($path, $mixinPath) {
        $this->path = $path;
        $this->mixinPath = $mixinPath;
        $this->oldestMod = time();
	}
    
    public function run() {
        while (true) {
            $this->checkFiles();
            sleep(1);
        }
    }
    
    protected function getFiles() {
        $dir_iterator = new RecursiveDirectoryIterator($this->path);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);
        $files = new RegexIterator($iterator, '/^.+\.scss$/i');
        return $files;
    }
    
    protected function checkFiles($force = false) {
        $files = $this->getFiles();
        foreach ($files as $file) {
            $this->checkFile($file, $force);
        }
    }
    
    protected function checkFile($file, $force) {
        $scssPath = $file->getPathname();
        $scssMod = $file->getMTime();
        $scssName = preg_replace('/^.+\/([^\/]+)$/', '\\1', $scssPath);

        // don't compile _-prefixed files
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
        
        // sys file changed, update all
        if (!$force && $this->sysMod > $this->oldestMod) {
            $this->checkFiles(true);
            $this->oldestMod = $this->sysMod;
        }        
    }
    
    protected function generateCss($scssPath, $cssPath) {
        echo substr($scssPath, strlen($this->path)) . "\n";
        $cssName = preg_replace('/^.+\/([^\/]+)$/', '\\1', $cssPath);
        $compiler = new Compiler();
        $compiler->setImportPaths($this->mixinPath);
        $compiler->setFormatter($this->formatter);
        $scss = file_get_contents($scssPath);
        $css = $compiler->compile($scss);
        file_put_contents($cssPath, $css);
        $this->generateSourceMap($cssPath, $css, $cssName);
    }
    
    protected function generateSourceMap($cssPath, $css, $cssName) {
        // remove old one
        if (file_exists($cssPath . '.map')) {
            unlink($cssPath . '.map');
        }
        // create new one
        // not spported...
    }    
    
}
