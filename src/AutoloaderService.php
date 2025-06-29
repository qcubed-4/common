<?php

/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

use Composer\Autoload\ClassLoader;
use Exception;

/**
 * Class Autoloader
 *
 * This is a kind of helper class for the autoloader. It is intended to be a companion class for the composer autoloader, but it
 * can also stand alone without the composer.
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
    /** @var  AutoloaderService|null the singleton service for this autoloader */
    protected static AutoloaderService|null $instance = null;

    /** @var bool */
    protected ?bool $blnInitialized = false;
    /** @var  ClassLoader */
    protected ClassLoader $composerAutoloader;
    /** @var  array */
    protected array $classmap;

    /**
     * Retrieve the current singleton, creating a new one if needed.
     *
     * @return AutoloaderService
     */


    public static function instance(): AutoloaderService
    {
        if (!static::$instance) {
            static::$instance = new AutoloaderService();
        }
        return static::$instance;
    }

    /**
     * Initialize the class by setting up the autoloader and optional vendor directory.
     *
     * @param string|null $strVendorDir The path to the vendor directory containing the composer autoloader. If null, skip composer autoloader setup.
     * @throws \Exception If the composer autoloader file is not found in the provided vendor directory.
     */
    public function initialize(?string $strVendorDir = null): static
    {
        $this->blnInitialized = true;
        $this->classmap = [];
        if ($strVendorDir !== null) {
            $strComposerAutoloadPath = $strVendorDir . '/autoload.php';
            if (file_exists($strComposerAutoloadPath)) {
                $this->composerAutoloader = require($strComposerAutoloadPath);
            }
            else {
                throw new Exception('Cannot find composer autoloader');
            }
        }

        // Register our autoloader, making sure we go after the composer autoloader
        spl_autoload_register(array($this, 'autoload'));
        return $this;
    }

    /**
     * Add a classmap, which is an array where keys are the all lowercase name of a class, and
     * the value is the absolute path to the file that holds that class.
     *
     * @param array $classmap
     * @return $this
     */
    public function addClassmap(array $classmap): static
    {
        $this->classmap = array_merge($this->classmap, $classmap);
        return $this;
    }

    /**
     * Add a PHP file that returns a classmap.
     *
     * @param string $strPath
     * @return $this
     */
    public function addClassmapFile(string $strPath): static
    {
        $this->classmap = array_merge($this->classmap, include($strPath));
        return $this;
    }

    /**
     * Autoload a class using the classmap if the class exists in it.
     *
     * @param string $strClassName The name of the class to be autoloader.
     * @return bool True if the class was successfully loaded, false otherwise.
     */
    public function autoload(string $strClassName): bool
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
    public function addPsr4(string $strPrefix, string $strPath): static
    {
        $this->composerAutoloader->addPsr4($strPrefix, $strPath);
        return $this;
    }

    /**
     * Find the file path corresponding to a given class name.
     *
     * @param string $strClass The fully qualified class name to locate.
     * @return string|null The file path of the class if found, or null if not found.
     */
    public function findFile(string $strClass): ?string
    {
        $strFile = $this->classmap[$strClass] ?? $this->composerAutoloader->findFile($strClass);

        if ($strFile && file_exists($strFile)) {
            return $strFile;
        }
        else {
            return null;
        }
    }
}