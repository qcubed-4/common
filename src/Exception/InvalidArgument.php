<?php
/**
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Exception;

/**
 * Thrown when a particular property of class is not defined, and we try to access it
 * @was QInvalidArgumentException
 */
class InvalidArgument extends Caller
{
    /**
     * Constructor method
     * @param string $strType
     * @param int $strClass
     * @param string $strProperty
     */
    public function __construct(string $strType, int $strClass, string $strProperty)
    {
        parent::__construct(sprintf("Invalid argument '%s' in '%s' class: %s", $strProperty,
            $strType, $strClass), 2);
    }
}
