<?php

/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

/**
 * Class Autoloader
 *
 * This is a kind of autoloader helper class. It is meant to be a partner class to the composer autoloader, but it
 * can stand alone as well without composer.
 *
 * Since composer has an autoloader, why do we need a new one?
 * 1) Composer's autoloader is great for development, but in production, you can get faster performance by having composer
 *    dump everything as a classmap and including that class map.
 * 2) You can get even FASTER performance by using a memory-mapping tool like https://github.com/sevenval/SHMT to cache
 *    the classmap.
 * 3) You might want to add your own class maps, not through the composer tool.
 *
 * @package QCubed
 */
class AutoloaderService
{
    /** @var  AutoloaderService the singleton service for this autoloader */
    protected static $instance;

    /** @var bool */
    protected $blnInitialized = false;
    /** @var  \Composer\Autoload\ClassLoader */
    protected $composerAutoloader;
    /** @var  array */
    protected $classmap;

    /**
     * Retrieve the current singleton, creating a new one if needed.
     *
     * @return AutoloaderService
     */
    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new AutoloaderService();
        }
        return static::$instance;
    }

    /**
     * Initialize the autoloader, passing the composer autoloader path if we are using it.
     *
     * @param string|null $strVendorDir Composer autoloader path (Optional)
     * @return $this
     */
    public function initialize($strVendorDir = null)
    {
        $this->blnInitialized = true;
        $this->classmap = [];
        if ($strVendorDir !== null) {
            $strComposerAutoloadPath = $strVendorDir . '/autoload.php';
            if (file_exists($strComposerAutoloadPath)) {
                $this->composerAutoloader = require($strComposerAutoloadPath);
            }
            else {
                throw new \Exception('Cannot find composer autoloader');
            }
        }

        // Register our autoloader, making sure we go after the composer autoloader
        spl_autoload_register(array($this, 'autoload'), true, false);
        return $this;
    }

    /**
     * Add a classmap, which is an array where keys are the all lowercase name of a class, and
     * the value is the absolute path to the file that holds that class.
     *
     * @param array $classmap
     * @return $this
     */
    public function addClassmap($classmap)
    {
        $this->classmap = array_merge($this->classmap, $classmap);
        return $this;
    }

    /**
     * Add a php file that returns a classmap.
     *
     * @param string $strPath
     * @return $this
     */
    public function addClassmapFile($strPath)
    {
        $this->classmap = array_merge($this->classmap, include($strPath));
        return $this;
    }

    /**
     * Our autoload function. Not meant for public consumption. Gets called by the system.
     * @param $strClassName
     * @return bool
     */
    public function autoload($strClassName)
    {
        $strClassName = strtolower($strClassName);
        if (!empty($this->classmap[$strClassName])) {
            $strPath = $this->classmap[$strClassName];
            if (file_exists($strPath)) {
                require_once($strPath);
                return true;
            }
        }
        return false;
    }

    /**
     * Adds a PSR-4 path to the autoloader. Currently only works with composer.
     *
     * TODO: If we do not have a composer autoloader, recursively search the directory and add all the classes found.
     *
     * @param string $strPrefix
     * @param string $strPath
     * @return $this
     */
    public function addPsr4($strPrefix, $strPath)
    {
        if ($this->composerAutoloader) {
            $this->composerAutoloader->addPsr4($strPrefix, $strPath);
        }
        return $this;
    }

    /**
     * Given a class, returns the path to the file that contains that class.
     *
     * @param $strClass
     * @return false|mixed|null|string
     */
    public function findFile($strClass)
    {
        if (isset($this->classmap[$strClass])) {
            $strFile = $this->classmap[$strClass];
        }
        else {
            $strFile = $this->composerAutoloader->findFile($strClass);
        }

        if ($strFile && file_exists($strFile)) {
            return $strFile;
        }
        else {
            return null;
        }
    }
}
