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
    /**
     * Constructor for initializing an exception with details about an undefined method in a class.
     *
     * @param string $strClass The name of the class where the method is undefined.
     * @param string $strMethod The name of the undefined method.
     * @return void
     */
    public function __construct(string $strClass, string $strMethod)
    {
        parent::__construct(sprintf("Undefined method in '%s' class: %s", $strClass, $strMethod),
            2);
    }
}
