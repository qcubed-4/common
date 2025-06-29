<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Js;

/**
 * Class ParameterList
 * A Wrapper class that will render an array without the brackets, so that it becomes a variable length parameter list.
 * @package QCubed\Js
 */
class ParameterList
{

    protected mixed $arrContent;

    /**
     * Constructor method.
     *
     * @param mixed $arrContent Content to initialize the object with.
     *
     * @return void
     */
    public function __construct(mixed $arrContent)
    {
        $this->arrContent = $arrContent;
    }

    /**
     * Converts the contents of the current object into a JavaScript-compatible object representation.
     *
     * @return string A JavaScript-compatible string representation of the object's contents.
     */
    public function toJsObject(): string
    {
        $strList = '';
        foreach ($this->arrContent as $objItem) {
            if (strlen($strList) > 0) {
                $strList .= ',';
            }
            $strList .= Helper::toJsObject($objItem);
        }
        return $strList;
    }
}