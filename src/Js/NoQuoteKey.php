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
 * Class NoQuoteKey
 * Wrapper class for arrays to control whether the key in the array is quoted.
 * In some situations, a quoted key has a different meaning from a non-quoted key.
 * For example, when making a list of parameters to pass when calling the jQuery $() command,
 * (i.e. $j(selector, params)), quoted words are turned into parameters, and non-quoted words
 * are turned into functions. For example, "size" will set the size attribute of the object, and
 * size (no quotes), will call the size() function on the object.
 *
 * To use it, simply wrap the value part of the array with this class.
 * @usage: $a = array ("click", new QJsNoQuoteKey (new QJsClosure('alert ("I was clicked")')));
 * @package QCubed\Js
 * @was QJsNoQuoteKey
 */
class NoQuoteKey implements \JsonSerializable
{
    protected $mixContent;

    /**
     * NoQuoteKey constructor.
     * @param $mixContent
     */
    public function __construct($mixContent)
    {
        $this->mixContent = $mixContent;
    }

    /**
     * @return string
     */
    public function toJsObject()
    {
        return Helper::toJsObject($this->mixContent);
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->mixContent;
    }
}
