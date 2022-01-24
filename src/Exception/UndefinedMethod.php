<?php
/**
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Exception;

/**
 * Thrown when we try to call an undefined method. Helpful for codegen.
 * @was QUndefinedMethodException
 */
class UndefinedMethod extends Caller
{
    public function __construct($strClass, $strMethod)
    {
        parent::__construct(sprintf("Undefined method in '%s' class: %s", $strClass, $strMethod),
            2);
    }
}
