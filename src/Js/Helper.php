<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed\Js;

use QCubed\Exception\Caller;

/**
 * Class Helper
 *
 * Helps with generating javascript code
 * @package QCubed\Js
 * @was JavaScriptHelper
 */
abstract class Helper
{
    const JSON_OBJECT_TYPE = 'qObjType';    // Indicates a PHP object we are serializing through the JsonSerialize interface

    /**
     * Helper class to convert a name from camel case to using dashes to separated words.
     * data-* html attributes have special conversion rules. Key names should always be lower case. Dashes in the
     * name get converted to camel case javascript variable names by jQuery.
     * For example, if you want to pass the value with key name "testVar" from PHP to javascript by printing it in
     * the html, you would use this function to help convert it to "data-test-var", after which you can retrieve
     * in in javascript by calling ".data('testVar')". on the object.
     * @param $strName
     * @return string
     * @throws Caller
     */
    public static function dataNameFromCamelCase($strName)
    {
        if (preg_match('/[A-Z][A-Z]/', $strName)) {
            throw new Caller('Not a camel case string');
        }
        return preg_replace_callback('/([A-Z])/',
            function ($matches) {
                return '-' . strtolower($matches[1]);
            },
            $strName
        );
    }

    /**
     * Converts an html data attribute name to camelCase.
     *
     * @param $strName
     * @return string
     */
    public static function dataNameToCamelCase($strName)
    {
        return preg_replace_callback('/-([a-z])/',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            $strName
        );
    }

    /**
     * Recursively convert a php object to a javascript object.
     * If the $objValue is an object other than Date and has a toJsObject() method, the method will be called
     * to perform the conversion. Array values are recursively converted as well.
     *
     * This string is designed to create the object if it was directly output to the browser. See toJSON below
     * for an equivalent version that is passable through a json interface.
     *
     * @static
     * @param mixed $objValue the php object to convert
     * @return string javascript representation of the php object
     */
    public static function toJsObject($objValue)
    {
        $strRet = '';

        switch (gettype($objValue)) {
            case 'double':
            case 'integer':
                $strRet = (string)$objValue;
                break;

            case 'boolean':
                $strRet = $objValue ? 'true' : 'false';
                break;

            case 'string':
                $strRet = self::jsEncodeString($objValue);
                break;

            case 'NULL':
                $strRet = 'null';
                break;

            case 'object':
                if (method_exists($objValue, 'toJsObject')) {
                    $strRet = $objValue->toJsObject();
                }
                break;

            case 'array':
                $array = (array)$objValue;
                if (0 !== count(array_diff_key($array, array_keys(array_keys($array))))) {
                    // associative array - create a hash
                    $strHash = '';
                    foreach ($array as $objKey => $objItem) {
                        if ($strHash) {
                            $strHash .= ',';
                        }
                        if ($objItem instanceof NoQuoteKey) {
                            $strHash .= $objKey . ': ' . self::toJsObject($objItem);
                        } else {
                            $strHash .= self::toJsObject($objKey) . ': ' . self::toJsObject($objItem);
                        }
                    }
                    $strRet = '{' . $strHash . '}';
                } else {
                    // simple array - create a list
                    $strList = '';
                    foreach ($array as $objItem) {
                        if (strlen($strList) > 0) {
                            $strList .= ',';
                        }
                        $strList .= self::toJsObject($objItem);
                    }
                    $strRet = '[' . $strList . ']';
                }

                break;

            default:
                $strRet = self::jsEncodeString((string)$objValue);
                break;

        }
        return $strRet;
    }

    public static function jsEncodeString($objValue)
    {
        // default to string if not specified
        static $search = array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"');
        static $replace = array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"');
        return '"' . str_replace($search, $replace, $objValue) . '"';
    }

    /**
     * Our specialized json encoder. Strings will be converted to UTF-8. Arrays will be recursively searched and
     * both keys and values made UTF-8. Objects will be converted with json_encode, and so objects that need a special
     * encoding should implement the jsonSerializable interface. See below
     * @param $objValue
     * @return string
     * @throws Caller
     */
    public static function toJSON($objValue)
    {
        assert('is_array($objValue) || is_object($objValue)');    // json spec says only arrays or objects can be encoded
        $objValue = self::makeJsonEncodable($objValue);
        $strRet = json_encode($objValue);
        if ($strRet === false) {
            throw new Caller('Json Encoding Error: ' . json_last_error_msg());
        }
        return $strRet;
    }

    /**
     * Convert an object to a structure that we can call json_encode on. This is particularly meant for the purpose of
     * sending json data to qcubed.js through ajax, but can be used for other things as well.
     *
     * PHP 5.4 has a new jsonSerializable interface that objects should use to modify their encoding if needed. Otherwise,
     * public member variables will be encoded. The goal of object serialization should be to be able to send it
     * to qcubed.unpackParams in qcubed.js to create the javascript form of the object. This decoder will look for objects
     * that have the 'qObjType' key set and send the object to the special unpacker.
     *
     * QDateTime handling is absent below. QDateTime objects will get converted, but not in a very useful way. If you
     * are using strict QDateTime objects (not likely since the framework normally uses QDateTime for all date objects),
     * you should convert them to QDateTime objects before sending them here.
     *
     * @param mixed $objValue
     * @return mixed
     * @was MakeJsonEncodable
     */
    public static function makeJsonEncodable($objValue)
    {
        if (QCUBED_ENCODING == 'UTF-8') {
            return $objValue; // Nothing to do, since all strings are already UTF-8 and objects can take care of themselves.
        }

        switch (gettype($objValue)) {
            case 'string':
                $objValue = mb_convert_encoding($objValue, 'UTF-8', QCUBED_ENCODING);
                return $objValue;

            case 'array':
                $newArray = array();
                foreach ($objValue as $key => $val) {
                    $key = self::makeJsonEncodable($key);
                    $val = self::makeJsonEncodable($val);
                    $newArray[$key] = $val;
                }
                return $newArray;

            default:
                return $objValue;

        }
    }

    /**
     * Utility function to make sure a script is terminated with a semicolon.
     *
     * @param $strScript
     * @return string
     * @was TerminateScript
     */
    public static function terminateScript($strScript)
    {
        if (!$strScript) {
            return '';
        }
        if (!($strScript = trim($strScript))) {
            return '';
        }
        if (substr($strScript, -1) != ';') {
            $strScript .= ';';
        }
        return $strScript . _nl();
    }
}
