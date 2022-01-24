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
 * @was QJsParameterList
 */
class ParameterList
{
    protected $arrContent;

    public function __construct($arrContent)
    {
        $this->arrContent = $arrContent;
    }

    public function toJsObject()
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
