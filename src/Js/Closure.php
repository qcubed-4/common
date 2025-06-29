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
 * Class Closure
 *
 * An object which represents a JavaScript closure (anonymous function). Use this to embed a
 * function into a PHP array or object that eventually will get turned into JavaScript.
 * @package QCubed\Js
 */
class Closure implements JsonSerializable
{
    /** @var  string The JS code for the function. */
    protected string $strBody;
    /** @var array|null parameter names for the function call that gets passed into the function. */
    protected ?array $strParamsArray = null;

    /**
     * @param string $strBody The function body
     * @param array|null $strParamsArray The names of the parameters passed in the function call
     */
    public function __construct(string $strBody, ?array $strParamsArray = null)
    {
        $this->strBody = $strBody;
        $this->strParamsArray = $strParamsArray;
    }

    /**
     * Return a JavaScript enclosure. Enclosures cannot be included in JSON, so we need to create a custom
     * encoding to include in the JSON that will get decoded on the other side.
     *
     * @return string
     */
    public function toJsObject(): string
    {
        $strParams = $this->strParamsArray ? implode(', ', $this->strParamsArray) : '';
        return 'function(' . $strParams . ') {' . $this->strBody . '}';
    }

    /**
     * Converts the object into something serializable by json_encode. Will get decoded in qcubed.unpackObj
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        // Encode in a way to decode in qcubed.js
        $a[Helper::JSON_OBJECT_TYPE] = 'qClosure';
        $a['func'] = $this->strBody;
        $a['params'] = $this->strParamsArray;
        return Helper::makeJsonEncodable($a);
    }
}
