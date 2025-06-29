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
 * Class NoQuoteKey
 * Wrapper class for arrays to control whether the key in the array is quoted.
 * In some situations, a quoted key has a different meaning from a non-quoted key.
 * For example, when making a list of parameters to pass when calling the jQuery $() command,
 * (i.e., $j(selector, params)), quoted words are turned into parameters, and non-quoted words
 * are turned into functions. For example, "size" will set the size attribute of the object, and
 * size (no quotes) will call the size() function on the object.
 *
 * To use it, simply wrap the value part of the array with this class.
 * @usage: $a = array ("click", new QJsNoQuoteKey (new Closure ('alert ("I was clicked")')));
 * @package QCubed\Js
 */
class NoQuoteKey implements JsonSerializable
{
    protected mixed $mixContent;

    /**
     * Constructor method to initialize the object with the provided content.
     *
     * @param mixed $mixContent The content to initialize the object with.
     *
     * @return void
     */
    public function __construct(mixed $mixContent)
    {
        $this->mixContent = $mixContent;
    }

    /**
     * Converts the object's mixContent property into a JavaScript-compatible string representation.
     *
     * @return string The JavaScript-compatible string representation of the mixContent property.
     */
    public function toJsObject(): string
    {
        return Helper::toJsObject($this->mixContent);
    }

    /**
     * Prepares the object's mixContent property for JSON serialization.
     *
     * @return mixed The data to be serialized into JSON format.
     */
    public function jsonSerialize(): mixed
    {
        return $this->mixContent;
    }
}