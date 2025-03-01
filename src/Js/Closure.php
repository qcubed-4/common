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
 * Class Closure
 *
 * An object which represents a javascript closure (annonymous function). Use this to embed a
 * function into a PHP array or object that eventually will get turned into javascript.
 * @package QCubed\Js
 * @was QJsClosure
 */
class Closure implements \JsonSerializable
{
    /** @var  string The js code for the function. */
    protected $strBody;
    /** @var array parameter names for the function call that get passed into the function. */
    protected $strParamsArray;

    /**
     * @param string $strBody The function body
     * @param array|null $strParamsArray The names of the parameters passed in the function call
     */
    public function __construct($strBody, $strParamsArray = null)
    {
        $this->strBody = $strBody;
        $this->strParamsArray = $strParamsArray;
    }

    /**
     * Return a javascript enclosure. Enclosures cannot be included in JSON, so we need to create a custom
     * encoding to include in the json that will get decoded at the other side.
     *
     * @return string
     */
    public function toJsObject()
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
