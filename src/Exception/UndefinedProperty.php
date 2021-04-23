<?php
/**
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Exception;

/**
 * Thrown when a particular property of class is not defined and we try to access it
 * @was QUndefinedPropertyException
 */
class UndefinedProperty extends Caller
{
    /**
     * Constructor method
     * @param string $strType
     * @param int $strClass
     * @param string $strProperty
     */
    public function __construct($strType, $strClass, $strProperty)
    {
        parent::__construct(sprintf("Undefined %s property or variable in '%s' class: %s",
            $strType, $strClass, $strProperty), 2);
    }
}
