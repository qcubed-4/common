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
 * Class QFolder
 * Handles folders(directories) located on your filesystem
 * @package QCubed
 * @was QFolder
 */
abstract class Folder
{
    /**
     * Same as mkdir but correctly implements directory recursion.
     * At its core, it will use the php MKDIR function.
     * This method does no special error handling.  If you want to use special error handlers,
     * be sure to set that up BEFORE calling MakeDirectory.
     *
     * @param         string $strPath actual path of the directoy you want created
     * @param         integer $intMode optional mode
     *
     * @return         boolean        the return flag from mkdir
     */
    public static function makeDirectory($strPath, $intMode = null)
    {
        if (is_dir($strPath)) {
            // Directory Already Exists
            return true;
        }

        // Check to make sure the parent(s) exist, or create if not
        if (!self::makeDirectory(dirname($strPath), $intMode)) {
            return false;
        }

        if (PHP_OS != "Linux") {
            // Create the current node/directory, and return its result
            $blnReturn = mkdir($strPath);

            if ($blnReturn && !is_null($intMode)) {
                // Manually CHMOD to $intMode (if applicable)
                // mkdir doesn't do it for mac, and this will error on windows
                // Therefore, ignore any errors that creep up
                $e = new Error\Handler();
                chmod($strPath, $intMode);
            }
        } else {
            $blnReturn = mkdir($strPath, $intMode);
        }

        return $blnReturn;
    }

    /**
     * Allows for deletion of non-empty directories - takes care of
     * recursion appropriately.
     *
     * @param    string $strPath Full path to the folder to be deleted
     *
     * @return    int    number of deleted files
     */
    public static function deleteFolder($strPath)
    {

        if (!is_dir($strPath)) {
            unlink($strPath);

            return 1;
        }

        $d = dir($strPath);
        $count = 0;
        while ($entry = $d->read()) {
            if ($entry != "." && $entry != "..") {
                if (is_dir($strPath)) {
                    $count += Folder::deleteFolder($strPath . "/" . $entry);
                }
            }
        }

        $d->close();
        rmdir($strPath);

        return $count;
    }

    /**
     * Tells whether a folder is writable or not.
     * Uses the QFile method underneath
     *
     * @param string $strPath Path to the folder.
     *
     * @return bool
     */
    public static function isWritable($strPath)
    {
        if ($strPath[strlen($strPath) - 1] != "/") {
            $strPath .= "/";
        }

        return File::isWritable($strPath);
    }

    /**
     * Traverse a particular path and get a list of files and folders
     * underneath that path. Optionally, also provide a regular
     * expression that specifies the pattern that the returned files must match.
     *
     * @param    string $strPath full path to the folder to be processed
     * @param    boolean $blnSkipFolders If this is set to true, only the FILES will be returned - not the folders.
     * @param    string $strFilenamePattern : optional string; regular expression that the files must match in order to be returned. If it's not set, all files in that folder will be returned.
     *
     * @return array
     */
    public static function listFilesInFolder($strPath, $blnSkipFolders = true, $strFilenamePattern = null)
    {
        // strip off the trailing slash if it's there
        if ($strPath[strlen($strPath) - 1] == "/") {
            $strPath = substr($strPath, 0, -1);
        }

        $result = array();

        $originalSet = self::getFilesInFolderHelper($strPath);
        if (!$originalSet) {
            return $result;
        }    // empty directory, or directory does not exist

        foreach ($originalSet as $item) {
            $childPath = $strPath . "/" . $item;
            if (is_dir($childPath)) {
                $childItems = self::listFilesInFolder($childPath);
                foreach ($childItems as $child) {
                    $result[] = $item . "/" . $child;
                }
            }

            if (!$blnSkipFolders || !is_dir($childPath)) {
                if (!$strFilenamePattern || ($strFilenamePattern && preg_match($strFilenamePattern, $item))) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * Helper function for getRecursiveFolderContents.
     * Returns the list of files in the folder that match a given pattern
     * Result is an array of strings of form "filename.extension".
     * Not recursive!
     *
     *
     * @param string $strPath full path to the folder to be processed
     *
     * @return array
     */
    private static function getFilesInFolderHelper($strPath)
    {
        // Remove trailing slash if it's there
        if ($strPath[strlen($strPath) - 1] == "/") {
            $strPath = substr($strPath, 0, -1);
        }
        $result = array();
        $dh = opendir($strPath);
        assert($dh !== false); // Does directory exist?
        if ($dh === false) {
            return [];
        }    // if asserts are off

        while (($file = readdir($dh)) !== false) {
            if ($file != "." && $file != "..") {
                if (!is_dir($file)) {
                    array_push($result, $file);
                }
            }
        }
        closedir($dh);

        return $result;
    }

    /**
     * Copy the contents of the source directory into the destination directory, creating the destination directory
     * and subdirectories if they do not exist. You can control whether to overwrite existing files or not.
     *
     * @param string $srcPath source directory
     * @param string $dstPath destination directory
     * @param boolean $blnOverwrite True to overwrite a file if it already exists. False to leave existing files alone.
     */

    public static function mergFolders($srcPath, $dstPath, $blnOverwrite)
    {
        if (!$srcPath || !is_dir($srcPath)) {
            return;
        }
        $dir = opendir($srcPath);

        try {
            if (!file_exists($dstPath)) {
                mkdir($dstPath);
            }
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($srcPath . '/' . $file)) {
                        self::copy_dir($srcPath . '/' . $file, $dstPath . '/' . $file, $blnOverwrite);
                    } else {
                        if ($blnOverwrite) {
                            copy($srcPath . '/' . $file, $dstPath . '/' . $file);
                        } else {

                            if (!file_exists($dstPath . '/' . $file)) {
                                copy($srcPath . '/' . $file, $dstPath . '/' . $file);
                            }
                        }
                    }
                }
            }
        }
        finally {
            closedir($dir);
        }
    }

    /**
     * Returns the number of items in a directory, whether they are files or other directories. Does not count the
     * dot and dot-dot items.
     * @param string $dir
     * @return int
     */
    public static function countItems($dir) {
        $fi = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        return iterator_count($fi);
    }

    /**
     * Removes all the items in the given directory, while preserving the directory itself.
     *
     * @param string $dir
     */
    public static function emptyContents($dir) {
        $iterator = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        if (!$iterator->valid()) {
            return;
        }
        foreach($iterator as $fileinfo) {
            if ($fileinfo->isDir()) {
                self::deleteFolder($fileinfo->getPathname());
            }
            else {
                unlink($fileinfo->getPathname());
            }
        }
    }
}