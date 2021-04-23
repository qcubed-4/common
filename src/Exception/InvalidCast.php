<?php

/**
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Exception;

/**
 * The exception that is thrown by QType::Cast
 * if an invalid cast is performed.  InvalidCastException
 * derives from CallerException, and therefore should be handled
 * similar to how CallerExceptions are handled (e.g. IncrementOffset should
 * be called whenever an InvalidCastException is caught and rethrown).
 * @was QInvalidCastException
 */
class InvalidCast extends Caller
{
    /**
     * Constructor
     * @param string $strMessage
     * @param int $intOffset
     */
    public function __construct($strMessage, $intOffset = 2)
    {
        parent::__construct($strMessage, $intOffset);
    }
}
