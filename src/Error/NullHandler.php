<?php

namespace QCubed\Error;

class NullHandler
{
    /**
     * Handles errors by processing the error details such as number, message, file, and line.
     *
     * @param int $errNum The level or amount of the error.
     * @param string $errStr The error message.
     * @param string $errFile The name of the file where the error occurred.
     * @param int $errLine The line number in the file where the error occurred.
     * @return void
     */
    public static function handleError(int $errNum, string $errStr, string $errFile, int $errLine)
    {
    }
}
