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
 * Class Func
 * Outputs a function call to a global function or function in an object referenced from global space. The purpose
 * of this is to immediately use the results of the function call, as opposed to a closure, which stores a pointer
 * to a function that is used later.
 * @package QCubed\Js
 */
class Func implements JsonSerializable
{
    /** @var  string|null */
    protected ?string $strContext = null;
    /** @var  string */
    protected string $strFunctionName;
    /** @var  array|null */
    protected ?array $params = null;

    /**
     * Func constructor.
     * @param string $strFunctionName The name of the function call.
     * @param array|null $params If given, the parameters to send it to the function call
     * @param string|null $strContext If given, the object in the window object which contains the function and is the context for the function.
     *   Use dot '.' notation to traverse the object tree. i.e. "obj1.obj2" refers to window.obj1.obj2 in javascript.
     */
    public function __construct(string $strFunctionName, ?array $params = null, ?string $strContext = null)
    {
        $this->strFunctionName = $strFunctionName;
        $this->params = $params;
        $this->strContext = $strContext;
    }

    /**
     * Returns this as a JavaScript string to be included in the end script of the page.
     * @return string
     */
    public function toJsObject(): string
    {
        if ($this->params) {
            $strParams = [];
            foreach ($this->params as $param) {
                $strParams[] = Helper::toJsObject($param);
            }
            $strParams = implode(",", $strParams);
        } else {
            $strParams = '';
        }
        $strFuncName = $this->strFunctionName;
        if ($this->strContext) {
            $strFuncName = $this->strContext . '.' . $strFuncName;
        }
        return $strFuncName . '(' . $strParams . ')';
    }

    /**
     * Returns this as a JSON object to be sent to qcubed.js during ajax drawing.
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $a[Helper::JSON_OBJECT_TYPE] = 'qFunc';
        $a['func'] = $this->strFunctionName;
        if ($this->strContext) {
            $a['context'] = $this->strContext;
        }
        if ($this->params) {
            $a['params'] = $this->params;
        }

        return Helper::makeJsonEncodable($a);
    }
}