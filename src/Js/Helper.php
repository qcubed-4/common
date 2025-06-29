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
 */
abstract class Helper
{
    public const JSON_OBJECT_TYPE = 'qObjType';    // Indicates a PHP object we are serializing through the JsonSerialize interface

    /**
     * Helper class to convert a name from camel case to using dashes to separated words.
     * Data-* HTML attributes have special conversion rules. Key names should always be lower case. Dashes in the
     * name get converted to camel case JavaScript variable names by jQuery.
     * For example, if you want to pass the value with the key name "testVar" from PHP to JavaScript by printing it in
     * the HTML, you would use this function to help convert it to "data-test-var", after which you can retrieve
     * in JavaScript by calling ".data('testVar')". On the object.
     *
     * @param string $strName
     *
     * @return string
     * @throws Caller
     */
    public static function dataNameFromCamelCase(string $strName): string
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
     * Converts a string with hyphen-separated words into camelCase format.
     * Each hyphen and the subsequent letter are replaced by the uppercase version of the letter.
     *
     * @static
     * @param string $strName The hyphen-separated string to convert.
     * @return string The camelCase formatted string.
     */
    public static function dataNameToCamelCase(string $strName): string
    {
        return preg_replace_callback('/-([a-z])/',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            $strName
        );
    }

    /**
     * Recursively convert a PHP object to a JavaScript object.
     * If the $objValue is an object other than Date and has a toJsObject() method, the method will be called
     * to perform the conversion. Array values are recursively converted as well.
     *
     * This string is designed to create the object if it was directly output to the browser. See toJSON below
     * for an equivalent version that is passable through a JSON interface.
     *
     * @static
     * @param mixed $objValue the PHP object to convert
     * @return string JavaScript's representation of the PHP object
     */
    public static function toJsObject(mixed $objValue): string
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
                    // an associative array - create a hash
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
                    // a simple array - create a list
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

    /**
     * Encodes a PHP string into a JSON-safe JavaScript string by escaping special characters.
     *
     * This method replaces special characters in the string, such as backslashes, line breaks,
     * and double quotes, with their corresponding escaped versions. The resulting string
     * is enclosed in double quotes to make it suitable for use in JavaScript contexts.
     *
     * @static
     * @param string $objValue The input string to encode.
     * @return string A JSON-safe JavaScript representation of the input string.
     */
    public static function jsEncodeString(string $objValue): string
    {
        // default to string if not specified
        static $search = array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"');
        static $replace = array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"');
        return '"' . str_replace($search, $replace, $objValue) . '"';
    }

    /**
     * Convert a PHP object or array to a JSON string.
     * Ensures that the object or array is converted into a JSON-escapable format before encoding.
     * Throw an exception if encoding fails.
     *
     * @static
     * @param mixed $objValue The PHP object or array to convert to JSON, expected to be either an array or object.
     * @return string A JSON-encoded string representation of the input value.
     * @throws Caller If a JSON encoding error occurs, an exception is thrown with the error message.
     */
    public static function toJSON(mixed $objValue): string
    {
        assert('is_array($objValue) || is_object($objValue)');    // JSON spec says only arrays or objects can be encoded
        $objValue = self::makeJsonEncodable($objValue);
        $strRet = json_encode($objValue);
        if ($strRet === false) {
            throw new Caller('JSON Encoding Error: ' . json_last_error_msg());
        }
        return $strRet;
    }

    /**
     * Convert an object to a structure that we can call json_encode on. This is particularly meant for the purpose of
     * sending JSON data to qcubed.js through ajax, but can be used for other things as well.
     *
     * PHP 5.4 has a new jsonSerializable interface that objects should use to modify their encoding if needed. Otherwise,
     * public member variables will be encoded. The goal of object serialization should be to be able to send it
     * to qcubed.unpackParams in qcubed.js to create the JavaScript form of the object. This decoder will look for objects
     * that have the 'qObjType' key set and send the object to the special unpacked.
     *
     * QDateTime handling is absent below. QDateTime objects will get converted, but not in a very useful way. If you
     * are using strict QDateTime objects (not likely since the framework normally uses QDateTime for all date objects),
     * you should convert them to QDateTime objects before sending them here.
     *
     * @param mixed $objValue
     * @return mixed
     */
    public static function makeJsonEncodable(mixed $objValue): mixed
    {
        if (QCUBED_ENCODING == 'UTF-8') {
            return $objValue; // There's nothing to do, as all strings are already UTF-8 and the objects can take care of themselves.
        }

        switch (gettype($objValue)) {
            case 'string':
                return mb_convert_encoding($objValue, 'UTF-8', QCUBED_ENCODING);

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
     * Ensures a given script string is properly formatted for termination.
     * Trims the input string, appends a semicolon if one is not already present,
     * and adds a newline at the end.
     *
     * @static
     * @param string $strScript The script strings to be terminated. If null or empty, an empty string will be returned.
     * @return string The properly terminated script string or an empty string if input is invalid.
     */
    public static function terminateScript(string $strScript): string
    {
        if (!$strScript) {
            return '';
        }
        if (!($strScript = trim($strScript))) {
            return '';
        }
        if (!str_ends_with($strScript, ';')) {
            $strScript .= ';';
        }
        return $strScript . _nl();
    }
}