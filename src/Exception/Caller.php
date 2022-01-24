<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Exception;

/**
 * This is the main exception to be thrown by any
 * method to indicate that the CALLER is responsible for
 * causing the exception.  This works in conjunction with QCubed's
 * error handling/reporting, so that the correct file/line-number is
 * displayed to the user.
 *
 * So for example, for a class that contains the method GetItemAtIndex($intIndex),
 * it is conceivable that the caller could call GetItemAtIndex(15), where 15 does not exist.
 * GetItemAtIndex would then thrown an IndexOutOfRangeException (which extends CallerException).
 * If the CallerException is not caught, then the Exception will be reported to the user.  The CALLER
 * (the script who CALLED GetItemAtIndex) would have that line highlighted as being responsible
 * for calling the error.
 *
 * The PHP default for exeception reporting would normally say that the "throw Exception" line in GetItemAtIndex
 * is responsible for throwing the exception.  While this is technically true, in reality, it was the line that
 * CALLED GetItemAtIndex which is responsible.  In short, this allows for much cleaner exception reporting.
 *
 * On a more in-depth note, in general, suppose a method OuterMethod takes in parameters, and ends up passing those
 * paremeters into ANOTHER method InnerMethod which could throw a CallerException.  OuterMethod is responsible
 * for catching and rethrowing the caller exception.  And when this is done, IncrementOffset() MUST be called on
 * the exception object, to indicate that OuterMethod's CALLER is responsible for the exception.
 *
 * So the code snippet to call InnerMethod by OuterMethod should look like:
 * <code>
 *    function outerMethod($mixValue) {
 *        try {
 *            innerMethod($mixValue);
 *        } catch (CallerException $objExc) {
 *            $objExc->incrementOffset();
 *            throw $objExc;
 *        }
 *        // Do Other Stuff
 *    }
 * </code>
 * Again, this will assure the user that the line of code responsible for the excpetion is properly being reported
 * by the QCubed error reporting/handler.
 *
 * @property-read int $Offset The exception offset.
 * @property-read string $BackTrace The exception backtrace.
 * @property-read string $TraceArray The exception backtrace in a form of an array.
 * @was QCallerException
 */
class Caller extends \Exception
{
    /**
     * @var int Exception offset
     *          The element in the stack trace array indicated by this index is marked
     *          as the point which caused the exception
     */
    private $intOffset;
    /** @var array The stack trace array as caputred by debug_backtrace() */
    private $strTraceArray;

    /**
     * The constructor of CallerExceptions.  Takes in a message string
     * as well as an optional Offset parameter (defaults to 1).
     * The Offset specifiies how many calls up the call stack is responsible
     * for the exception.  By definition, when a CallerException is called,
     * at the very least the Caller of the most immediate function, which is
     * 1 up the call stack, is responsible.  So therefore, by default, intOffset
     * is set to 1.
     *
     * It is rare for intOffset to be set to an integer other than 1.
     *
     * Normally, the Offset would be altered by calls to IncrementOffset
     * at every step the CallerException is caught/rethrown up the call stack.
     *
     * @param string $strMessage the Message of the exception
     * @param integer $intOffset the optional Offset value (currently defaulted to 1)
     */
    public function __construct($strMessage, $intOffset = 1)
    {
        parent::__construct($strMessage);
        $this->intOffset = $intOffset;
        $this->strTraceArray = debug_backtrace();

        if (isset($this->strTraceArray[$this->intOffset]['file'])) {
            $this->file = $this->strTraceArray[$this->intOffset]['file'];
            $this->line = $this->strTraceArray[$this->intOffset]['line'];
        }
    }

    /**
     * Set message for the exception
     *
     * @param string $strMessage
     */
    public function setMessage($strMessage)
    {
        $this->message = $strMessage;
    }

    /**
     * Increment the offset of the backtrace to hid the current level of code and point to caller.
     * @was IncrementOffset
     */
    public function incrementOffset()
    {
        $this->intOffset++;
        if (array_key_exists('file', $this->strTraceArray[$this->intOffset])) {
            $this->file = $this->strTraceArray[$this->intOffset]['file'];
        } else {
            $this->file = '';
        }
        if (array_key_exists('line', $this->strTraceArray[$this->intOffset])) {
            $this->line = $this->strTraceArray[$this->intOffset]['line'];
        } else {
            $this->line = '';
        }
    }

    /**
     * Decrement the backtrace, restoring an increment
     * @was DecrementOffset
     */
    public function decrementOffset()
    {
        $this->intOffset--;
        if (array_key_exists('file', $this->strTraceArray[$this->intOffset])) {
            $this->file = $this->strTraceArray[$this->intOffset]['file'];
        } else {
            $this->file = '';
        }
        if (array_key_exists('line', $this->strTraceArray[$this->intOffset])) {
            $this->line = $this->strTraceArray[$this->intOffset]['line'];
        } else {
            $this->line = '';
        }
    }

    /**
     * PHP magic method
     * @param $strName
     * @return array|int|mixed
     * @throws \Exception
     */
    public function __get($strName)
    {
        switch ($strName) {
            case "Offset":
                return $this->intOffset;

            case "BackTrace":
                $objTraceArray = debug_backtrace();
                return (var_export($objTraceArray, true));

            case "TraceArray":
                return $this->strTraceArray;

            case "ErrorNumber":
                return 0;   // If we get here, we just need to return a default value.


            default:
                throw new \Exception("Unknown property " . $strName);

        }
    }
}
