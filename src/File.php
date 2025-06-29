<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

use Exception;

/**
 * Class QFile
 * Handles reading and writing of files on the file system
 * @package QCubed
 * @was QFile
 */
abstract class File
{
    /**
     * Reads the contents of a file at the specified path.
     * The method reads the file in chunks of 8000 bytes until the end of the file is reached.
     *
     * @param string $strFilePath The path to the file to be read.
     * @return string The content of the file as a string.
     */
    public static function readFile(string $strFilePath): string
    {
        $result = "";
        $handle = fopen($strFilePath, "r");
        while (!feof($handle)) {
            $result .= fread($handle, 8000);
        }
        fclose($handle);

        return $result;
    }

    /**
     * Write data into a file
     *
     * @param string $strFilePath Path of the file into which to write
     * @param string $strContents The contents that should be written into the file
     *
     * @throws Exception
     */
    public static function writeFile(string $strFilePath, string $strContents): void
    {
        $fileHandle = fopen($strFilePath, "w");
        if (!$fileHandle) {
            throw new Exception("Cannot open a file by writing: " . $strFilePath);
        }

        if (fwrite($fileHandle, $strContents, strlen($strContents)) === false) {
            throw new Exception("Unable to write a file: " . $strFilePath);
        }
        fclose($fileHandle);
    }

    /**
     * Will work despite Windows ACLs bug
     * NOTE: use a trailing slash for folders!!!
     * See http://bugs.php.net/bug.php?id=27609 AND http://bugs.php.net/bug.php?id=30931
     * Source: <http://www.php.net/is_writable#73596>
     * @param string $path
     * @return bool
     */
    public static function isWritable(string $path): bool
    {
        // recursively return a temporary file path
        if ($path[strlen($path) - 1] == '/') {
            return self::isWritable($path . uniqid(mt_rand()) . '.tmp');
        } elseif (is_dir($path)) {
            return self::isWritable($path . '/' . uniqid(mt_rand()) . '.tmp');
        }

        // check a file for read/write capabilities
        $rm = file_exists($path);
        $handle = @fopen($path, 'a');

        if ($handle === false) {
            return false;
        }

        fclose($handle);

        if (!$rm) {
            unlink($path);
        }

        return true;
    }
}