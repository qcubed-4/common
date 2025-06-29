<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Error;

/**
 * Class Handler
 *
 * An object you can create locally that will temporarily change the error handler to the given function.
 *
 * @package QCubed\Error
 */
class Handler
{
    protected ?int $intStoredErrorLevel = null;

    /**
     * Handler constructor.
     *
     * @param callable|null $func A callable that will be used temporarily as the function
     * @param null $intLevel
     */
    public function __construct(?callable $func = null, $intLevel = null)
    {
        if (!$func) {
            // No Error Handling is wanted -- simulate an "On Error, Resume" type of functionality
            set_error_handler('\\QCubed\\Error\\Manager::handleError', 0); // invalidate our default handler
            $this->intStoredErrorLevel = error_reporting(0); // turn off all error reportings
        } else {
            set_error_handler($func, $intLevel);
            $this->intStoredErrorLevel = -1;
        }
    }

    /**
     * Restores the temporarily overridden default error handling mechanism back to the default.
     */
    public function restore(): void
    {
        if ($this->intStoredErrorLevel != -1) {
            error_reporting($this->intStoredErrorLevel);
        }
        restore_error_handler();
        $this->intStoredErrorLevel = null;
    }

    /**
     * Class destructor.
     *
     * Restores the state modified during the lifetime of the object.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->restore();
    }
}