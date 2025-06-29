<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Js;

use JsonSerializable;

/**
 * Class VarName
 * Outputs a string without quotes to specify a global variable name. Strings are normally quoted. Dot notation
 * can be used to specify items within globals.
 * @package QCubed\Js
 */
class VarName implements JsonSerializable
{

    protected mixed $strContent;

    /**
     * Constructor method to initialize the class with provided content.
     *
     * @param mixed $strContent The content to initialize the object with.
     *
     * @return void
     */
    public function __construct(mixed $strContent)
    {
        $this->strContent = $strContent;
    }

    /**
     * Converts the content to a JavaScript object representation.
     *
     * @return string The JavaScript object as a string.
     */
    public function toJsObject(): string
    {
        return $this->strContent;
    }

    /**
     * Prepares the object for JSON serialization.
     *
     * @return mixed A JSON-encodable representation of the object.
     */
    public function jsonSerialize(): mixed
    {
        $a[Helper::JSON_OBJECT_TYPE] = 'qVarName';
        $a['varName'] = $this->strContent;
        return Helper::makeJsonEncodable($a);
    }
}