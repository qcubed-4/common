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
 * Class VarName
 * Outputs a string without quotes to specify a global variable name. Strings are normally quoted. Dot notation
 * can be used to specify items within globals.
 * @package QCubed\Js
 * @was QJsVarName
 */
class VarName implements \JsonSerializable
{
    protected $strContent;

    public function __construct($strContent)
    {
        $this->strContent = $strContent;
    }

    public function toJsObject()
    {
        return $this->strContent;
    }

    public function jsonSerialize(): mixed
    {
        $a[Helper::JSON_OBJECT_TYPE] = 'qVarName';
        $a['varName'] = $this->strContent;
        return Helper::makeJsonEncodable($a);
    }
}
