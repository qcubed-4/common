<?php
    /**
     *
     */

namespace QCubed\Error;

use QCubed\Exception\DataBind;
use QCubed\Timer;
use QCubed\ErrorAttribute;
use Throwable;
use ReflectionObject;

class Manager
{
    protected static ?bool $errorFlag = false; // indicates an error occurred

    /**
     * Checks if an error condition is currently set.
     *
     * @return bool Returns true if the error flag is set, false otherwise.
     */
    public static function isError(): bool
    {
        return static::$errorFlag;
    }


    /**
     * Set Error/Exception Handling to the default
     * QCubed HandleError and HandleException functions
     * (Only in non-CLI mode)
     *
     * Feel free to change, if needed, to your own
     * custom error handling script(s).
     */
    public static function initialize(): void
    {
        if (array_key_exists('SERVER_PROTOCOL', $_SERVER)) {
            set_error_handler(['\\QCubed\\Error\\Manager', 'handleError'], error_reporting());
            set_exception_handler(['\\QCubed\\Error\\Manager', 'handleException']);
            register_shutdown_function(['\\QCubed\\Error\\Manager', 'shutdown']);
        }
    }

    /**
     * Handles an error based on its severity, providing detailed error information.
     * Displays the error using a specified error handler and returns a boolean indicating if
     * the error was handled successfully.
     *
     * @param int $errNum The level of the error raised, e.g., E_WARNING or E_ERROR.
     * @param string $errStr The error message.
     * @param string $errFile The filename where the error was raised.
     * @param int $errLine The line number where the error was raised.
     * @return bool False if the error is displayed, true if the error is suppressed or already being handled.
     */
    public static function handleError(int $errNum, string $errStr, string $errFile, int $errLine): bool
    {
        // If a command is called with "@", then we should return
        if (error_reporting() == 0) {
            return true;
        }

        if (!self::$errorFlag) {
            self::$errorFlag = true;
        } else {
            return true; // Already are handling an error. Indicates an additional error condition during error handling
        }

        $code = match ($errNum) {
            E_ERROR => "E_ERROR",
            E_WARNING => "E_WARNING",
            E_PARSE => "E_PARSE",
            E_NOTICE => "E_NOTICE",
            E_STRICT => "E_STRICT",
            E_CORE_ERROR => "E_CORE_ERROR",
            E_CORE_WARNING => "E_CORE_WARNING",
            E_COMPILE_ERROR => "E_COMPILE_ERROR",
            E_COMPILE_WARNING => "E_COMPILE_WARNING",
            E_USER_ERROR => "E_USER_ERROR",
            E_USER_WARNING => "E_USER_WARNING",
            E_USER_NOTICE => "E_USER_NOTICE",
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            default => "Unknown",
        };

        static::displayError(
            "Error",
            $errNum,
            $code,
            $errStr,
            $errFile,
            $errLine,
            self::getBacktrace(),
            (array)null
        );
        return false;
    }

    /**
     * Returns a stringifies version of a backtrace.
     * Set $blnShowArgs if you want to see a representation of the arguments. Note that if you are sending
     * in objects, this will unpack the entire structure and display its contents.
     * $intSkipTraces is how many back traces you want to skip. Set this to at least one to skip the
     * calling of this function itself.
     *
     * @param bool $blnShowArgs Determines whether to include function arguments in the backtrace. Defaults to false.
     * @param int $intSkipTraces Specifies the number of backtrace entries to skip from the start. Defaults to 1.
     * @return string A formatted string representation of the backtrace, including a file, line, class, type, function, and arguments (if enabled).
     */
    public static function getBacktrace(?bool $blnShowArgs = false, int $intSkipTraces = 1): string
    {
        if (!$blnShowArgs) {
            $b = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        } else {
            $b = debug_backtrace(false);
        }
        $strRet = "";
        for ($i = $intSkipTraces; $i < count($b); $i++) {
            $item = $b[$i];

            $strFile = (array_key_exists("file", $item)) ? $item["file"] : "";
            $strLine = (array_key_exists("line", $item)) ? $item["line"] : "";
            $strClass = (array_key_exists("class", $item)) ? $item["class"] : "";
            $strType = (array_key_exists("type", $item)) ? $item["type"] : "";
            $strFunction = (array_key_exists("function", $item)) ? $item["function"] : "";

            $vals = [];
            if (!empty($item["args"])) {
                foreach ($item["args"] as $val) {
                    $vals[] = print_r($val, true);
                }
            }
            $strArgs = implode(", ", $vals);

            $strRet .= sprintf("#%s %s(%s): %s%s%s(%s)\n",
                $i,
                $strFile,
                $strLine,
                $strClass,
                $strType,
                $strFunction,
                $strArgs);
        }

        return $strRet;
    }

/**
* Handles an exception or error by extracting its details and displaying an error message.
* Prevents recursive error handling by ensuring it is invoked only once per error occurrence.
*
* @param Throwable $__exc_objException The exception or error object to be handled.
*                                       Extract information such as error number, message,
*                                       filename, line number, stack trace, and additional attributes if applicable.
*
* @return void This method does not return any value. It processes and displays error details.
*/
    public static function handleException(Throwable $__exc_objException): void
    {
        if (!self::$errorFlag) {
            self::$errorFlag = true;
        } else {
            return; // Already are handling an error. Indicates an additional error condition during error handling
        }

        global $__exc_strType;
        if (isset($__exc_strType)) {
            return; // Error was already called, avoid endless looping
        }

        $__exc_objReflection = new ReflectionObject($__exc_objException);

        $__exc_strType = "Throwable"; // Accepts both Exception and Error
        $__exc_errno = property_exists($__exc_objException, 'ErrorNumber')
            ? $__exc_objException->ErrorNumber
            : 0;
        $__exc_strMessage = $__exc_objException->getMessage();
        $__exc_strObjectType = $__exc_objReflection->getName();

        $__exc_objErrorAttributeArray = [];

        if ($__exc_objException instanceof DataBind) {
            if ($__exc_objException->Query) {
                $__exc_objErrorAttribute = new ErrorAttribute("Query", $__exc_objException->Query, true);
                $__exc_objErrorAttributeArray[1] = $__exc_objErrorAttribute;
            }
        }

        $__exc_strFilename = $__exc_objException->getFile();
        $__exc_intLineNumber = $__exc_objException->getLine();
        $__exc_strStackTrace = trim($__exc_objException->getTraceAsString());

        self::displayError(
            $__exc_strType,
            $__exc_errno,
            $__exc_strObjectType,
            $__exc_strMessage,
            $__exc_strFilename,
            $__exc_intLineNumber,
            $__exc_strStackTrace,
            $__exc_objErrorAttributeArray
        );
    }

    /**
     * Handles and displays error messages, including optional rendering of an error page if defined.
     * Outputs error details and stops script execution upon invocation.
     *
     * @param string $__exc_strType The type of the error, such as 'Fatal', 'Warning', or 'Notice'.
     * @param int $__exc_errno The error code or number associated with the error.
     * @param string $__exc_strObjectType The type of object that caused the error, if applicable.
     * @param string $__exc_strMessage The detailed error message describing the issue.
     * @param string $__exc_strFilename The name of the file in which the error occurred.
     * @param int $__exc_intLineNumber The line number in the file where the error occurred.
     * @param string $__exc_strStackTrace The stack trace detailing the call stack at the time of the error.
     * @param array $__exc_objErrorAttributeArray An optional array of additional error attributes or metadata.
     * @return void Does not return any value. This method halts execution of the script after displaying the error.
     */
    protected static function displayError(
        string $__exc_strType,
        int    $__exc_errno,
        string $__exc_strObjectType,
        string $__exc_strMessage,
        string $__exc_strFilename,
        int    $__exc_intLineNumber,
        string $__exc_strStackTrace,
        array $__exc_objErrorAttributeArray

    ): void
    {
        if (ob_get_length()) {
            $__exc_strRenderedPage = ob_get_contents();
            ob_clean();
        }
        if (defined('QCUBED_ERROR_PAGE_PHP')) {
            require(QCUBED_ERROR_PAGE_PHP);
        } else {
            // Error in installer or similar - QCUBED_ERROR_PAGE_PHP constant is not defined yet.
            echo "error: errno: " . $__exc_errno . "<br/>" . $__exc_strMessage . "<br/>" . $__exc_strFilename . ":" . $__exc_intLineNumber;
        }
        exit();
    }

    /**
     * Handles application shutdown tasks such as outputting timer information and handling any fatal errors.
     * If a timer output file is defined and the Timer class is available, this function writes the timer output to the file.
     * Additionally, it checks for any fatal errors that occurred before shutdown and processes them if detected.
     *
     * @return void
     */

    public static function shutdown(): void
    {
        if (defined('QCUBED_TIMER_OUT_FILE') && class_exists('\\QCubed\\Timer')) {
            $strTimerOutput = Timer::VarDump(false);
            //if ($strTimerOutput) {
                //file_put_contents(QCUBED_TIMER_OUT_FILE, $strTimerOutput . "\n", FILE_APPEND);
            //}
        }

        $error = error_get_last();
        if ($error &&
            is_array($error)
            /*&&
            (!defined('QCodeGen::DebugMode') || QCodeGen::DebugMode)*/
        ) { // if we are code genning, only error if we are in debug mode. Prevents chmod error.

            self::handleError (
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

}