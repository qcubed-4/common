<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;

/**
 * Type Library to add some support for strongly named types.
 *
 * PHP does not support strongly named types.  The QCubed type library
 * and QCubed typing in general attempts to bring some structure to types
 * when passing in values, properties, parameters to/from QCubed framework objects
 * and methods.
 *
 * The Type library attempts to allow as much flexibility as possible to
 * set and cast variables to other types, similar to how PHP does it natively,
 * but simply adds a big more structure to it.
 *
 * For example, regardless if a variable is an integer, boolean, or string,
 * Type::Cast will allow the flexibility of those values to interchange with
 * each other with little to no issue.
 *
 * In addition to value objects (ints, bools, floats, strings), the Type library
 * also supports object casting.  While technically casting one object to another
 * is not a true cast, Type::Cast does at least ensure that the tap being "casted"
 * to is a legitamate subclass of the object being "cast".  So if you have ParentClass,
 * and you have a ChildClass that extends ParentClass,
 *        $objChildClass = new ChildClass();
 *        $objParentClass = new ParentClass();
 *        Type::cast($objChildClass, 'ParentClass'); // is a legal cast
 *        Type::cast($objParentClass, 'ChildClass'); // will throw an InvalidCastException
 *
 * For values, specifically int to string conversion, one different between
 * Type::Cast and PHP (in order to add structure) is that if an integer contains
 * alpha characters, PHP would normally allow that through w/o complaint, simply
 * ignoring any numeric characters past the first alpha character.  Type::Cast
 * would instead throw an InvalidCastException to let the developer immedaitely
 * know that something doesn't look right.
 *
 * In theory, the type library should maintain the same level of flexibility
 * PHP developers are accostomed to, while providing a mechanism to limit
 * careless coding errors and tough to figure out mistakes due to PHP's sometimes
 * overly laxed type conversions.
 * @was QType
 */
class Type
{
    const STRING = 'string';
    const INTEGER = 'integer';
    const FLOAT = 'double';
    const BOOLEAN = 'boolean';
    const OBJECT = 'object';
    const ARRAY_TYPE = 'array';
    const DATE_TIME = '\\QCubed\\QDateTime';
    const RESOURCE = 'resource';
    const CALLABLE_TYPE = 'callable'; // Callable Type  - Note: For QCubed, Type::CALLABLE_TYPEs CANNOT be Closures (because they cannot be serialized into the form state)
    const ASSOCIATION = 'association';

    // Virtual types
    const REVERSE_REFERENCE = 'reverse_reference';
    const NO_OP = 1;
    const CHECK_ONLY = 2;
    const CAST_ONLY = 3;
    const CHECK_AND_CAST = 4;
    private static $intBehaviour = Type::CHECK_AND_CAST;

    /**
     * This faux constructor method throws a caller exception.
     * The Type object should never be instantiated, and this constructor
     * override simply guarantees it.
     *
     * @throws Caller
     * @return Type
     */
    public final function __construct()
    {
        throw new Caller('Type should never be instantiated.  All methods and variables are publically statically accessible.');
    }

    /**
     * This method can be used to change the casting behaviour of Type::cast().
     * By default Type::cast() does lots of validation and type casting (using settype()).
     * Depending on your application you may or may not need validation or casting or both.
     * In these situations you can set the necessary behaviour by passing the appropriate constant to this function.
     *
     * @static
     * @param int $intBehaviour one of the 4 constants Type::NO_OP, Type::CAST_ONLY, Type::CHECK_ONLY, Type::CHECK_AND_CAST
     * @return int the previous setting
     */
    public static function setBehaviour($intBehaviour)
    {
        $oldBehaviour = Type::$intBehaviour;
        Type::$intBehaviour = $intBehaviour;
        return $oldBehaviour;
    }

    /**
     * Used by the QCubed Code Generator to allow for the code generation of
     * the actual "Type::Xxx" constant, instead of the text of the constant,
     * in generated code.
     * It is rare for Constant to be used manually outside of Code Generation.
     *
     * @param string $strType the type to convert to 'constant' form
     *
     * @return string the text of the Text:Xxx Constant
     * @throws InvalidCast
     */
    public final static function constant($strType)
    {
        switch ($strType) {
            case Type::OBJECT:
                return 'QCubed\\Type::OBJECT';
            case Type::STRING:
                return 'QCubed\\Type::STRING';
            case Type::INTEGER:
                return 'QCubed\\Type::INTEGER';
            case Type::FLOAT:
                return 'QCubed\\Type::FLOAT';
            case Type::BOOLEAN:
                return 'QCubed\\Type::BOOLEAN';
            case Type::ARRAY_TYPE:
                return 'QCubed\\Type::ARRAY_TYPE';
            case Type::RESOURCE:
                return 'QCubed\\Type::RESOURCE';
            case Type::DATE_TIME:
                return 'QCubed\\Type::DATE_TIME';

            default:
                // Could not determine type
                throw new InvalidCast(sprintf('Unable to determine type of item to lookup its constant: %s', $strType));
        }
    }

    /**
     * Used by the QCubed Code Generator and QSoapService class to allow for the xml generation of
     * the actual "s:type" Soap Variable types.
     *
     * @param string $strType the type to convert to 'constant' form
     *
     * @return string the text of the SOAP standard s:type variable type
     * @throws InvalidCast
     */
    public final static function soapType($strType)
    {
        switch ($strType) {
            case Type::STRING:
                return 'string';
            case Type::INTEGER:
                return 'int';
            case Type::FLOAT:
                return 'float';
            case Type::BOOLEAN:
                return 'boolean';
            case Type::DATE_TIME:
                return 'dateTime';

            case Type::ARRAY_TYPE:
            case Type::OBJECT:
            case Type::RESOURCE:
            default:
                // Could not determine type
                throw new InvalidCast(sprintf('Unable to determine type of item to lookup its constant: %s', $strType));
        }
    }

    private static function castObjectTo($objItem, $strType)
    {
        try {
            $objReflection = new \ReflectionClass($objItem);
            $strObjName = $objReflection->getName();
            if ($strObjName == 'SimpleXMLElement') {
                switch ($strType) {
                    case Type::STRING:
                        return (string)$objItem;
                    case Type::INTEGER:
                        try {
                            return Type::cast((string)$objItem, Type::INTEGER);
                        } catch (Caller $objExc) {
                            $objExc->incrementOffset();
                            throw $objExc;
                        }
                    case Type::BOOLEAN:
                        $strItem = strtolower(trim((string)$objItem));
                        if (($strItem == 'false') ||
                            (!$strItem)
                        ) {
                            return false;
                        } else {
                            return true;
                        }
                }
            } elseif ($strObjName == 'Closure') {
                if ($strType == Type::CALLABLE_TYPE) {
                    throw new \Exception("Can't use a closure here"); // will get rethrown below, but this will error to
                    // prevent you from accidentally sending a Closure to a callable in a form object.
                    // that cannot be done, because Closures are not serializable. Some other forms of
                    // callables ARE serializable though, so use that instaed.
                }
            }

            if ($objItem instanceof $strType) {
                return $objItem;
            }

            if ($strType == Type::STRING) {
                return (string)$objItem;    // invokes __toString() magic method
            }
        } catch (\Exception $objExc) {
        }

        throw new InvalidCast(sprintf('Unable to cast %s object to %s', $objReflection->getName(), $strType));
    }

    /**
     * Used to cast a variable to another type.  Allows for moderate
     * support of strongly-named types.
     * Will throw an exception if the cast fails, causes unexpected side effects,
     * if attempting to cast an object to a value (or vice versa), or if an object
     * is being cast to a class that isn't a subclass (e.g. parent).  The exception
     * thrown will be an InvalidCastException, which extends CallerException.
     *
     * @param mixed $mixItem the value, array or object that you want to cast
     * @param string $strType the type to cast to.  Can be a Type::XXX constant (e.g. Type::INTEGER), or the name of a Class
     *
     * @return mixed the passed in value/array/object that has been cast to strType
     * @throws \Exception|Caller|InvalidCast
     */
    public final static function cast($mixItem, $strType)
    {
        switch (Type::$intBehaviour) {
            case Type::NO_OP:
                return $mixItem;
            case Type::CAST_ONLY:
                throw new Caller("Type::CAST_ONLY handling not yet implemented");
                break;
            case Type::CHECK_ONLY:
                throw new Caller("Type::CHECK_ONLY handling not yet implemented");
                break;
            case Type::CHECK_AND_CAST:
                break;
            default:
                throw new \Exception('Unknown Type behavior');
                break;
        }
        // Automatically Return NULLs
        if (is_null($mixItem)) {
            return null;
        }

        // Figure out what PHP thinks the type is
        $strPhpType = gettype($mixItem);

        switch ($strPhpType) {
            case Type::OBJECT:
                try {
                    return Type::castObjectTo($mixItem, $strType);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }

            case Type::STRING:
            case Type::INTEGER:
            case Type::FLOAT:
            case Type::BOOLEAN:
                try {
                    return Type::castValueTo($mixItem, $strType);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }

            case Type::ARRAY_TYPE:
                try {
                    return Type::castArrayTo($mixItem, $strType);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }

            case Type::RESOURCE:
                // Cannot Cast Resources
                throw new InvalidCast('Resources cannot be cast');

            default:
                // Could not determine type
                throw new InvalidCast(sprintf('Unable to determine type of item to be cast: %s', $mixItem));
        }
    }

    private static function castValueTo($mixItem, $strNewType)
    {
        $strOriginalType = gettype($mixItem);

        switch (Type::typeFromDoc($strNewType)) {
            case Type::BOOLEAN:
                if ($strOriginalType == Type::BOOLEAN) {
                    return $mixItem;
                }
                if (is_null($mixItem)) {
                    return false;
                }
                if (strlen($mixItem) == 0) {
                    return false;
                }
                if (strtolower($mixItem) == 'false') {
                    return false;
                }
                settype($mixItem, $strNewType);
                return $mixItem;

            case Type::INTEGER:
                if ($strOriginalType == Type::BOOLEAN) {
                    throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                        $mixItem));
                }
                if (strlen($mixItem) == 0) {
                    return null;
                }
                if ($strOriginalType == Type::INTEGER) {
                    return $mixItem;
                }

                // Check to make sure the value hasn't changed significantly
                $intItem = $mixItem;
                settype($intItem, $strNewType);
                $mixTest = $intItem;
                settype($mixTest, $strOriginalType);

                // If the value hasn't changed, it's safe to return the casted value
                if ((string)$mixTest === (string)$mixItem) {
                    return $intItem;
                }

                // if casting changed the value, but we have a valid integer, return with a string cast
                if (preg_match('/^-?\d+$/', $mixItem) === 1) {
                    return (string)$mixItem;
                }

                // any other scenarios is an invalid cast
                throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                    $mixItem));
            case Type::FLOAT:
                if ($strOriginalType == Type::BOOLEAN) {
                    throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                        $mixItem));
                }
                if (strlen($mixItem) == 0) {
                    return null;
                }
                if ($strOriginalType == Type::FLOAT) {
                    return $mixItem;
                }

                if (!is_numeric($mixItem)) {
                    throw new InvalidCast(sprintf('Invalid float: %s', $mixItem));
                }

                // Check to make sure the value hasn't changed significantly
                $fltItem = $mixItem;
                settype($fltItem, $strNewType);
                $mixTest = $fltItem;
                settype($mixTest, $strOriginalType);

                //account for any scientific notation that results
                //find out what notation is currently being used
                $i = strpos($mixItem, '.');
                $precision = ($i === false) ? 0 : strlen($mixItem) - $i - 1;
                //and represent the casted value the same way
                $strTest = sprintf('%.' . $precision . 'f', $fltItem);

                // If the value hasn't changed, it's safe to return the casted value
                if ((string)$strTest === (string)$mixItem) {
                    return $fltItem;
                }

                // the changed value could be the result of loosing precision. Return the original value with no cast
                return $mixItem;

            case Type::STRING:
                if ($strOriginalType == Type::STRING) {
                    return $mixItem;
                }

                // Check to make sure the value hasn't changed significantly
                $strItem = $mixItem;
                settype($strItem, $strNewType);
                $mixTest = $strItem;
                settype($mixTest, $strOriginalType);

                // Has it?
                $blnSame = true;
                if ($strOriginalType == Type::FLOAT) {
                    // type conversion from float to string affects precision and can throw off the comparison
                    // so we need to use a comparison check using an epsilon value instead
                    //$epsilon = 1.0e-14; too small
                    $epsilon = 1.0e-11;
                    $diff = abs($mixItem - $mixTest);
                    if ($diff > $epsilon) {
                        $blnSame = false;
                    }
                } else {
                    if ($mixTest != $mixItem) {
                        $blnSame = false;
                    }
                }
                if (!$blnSame) //This is an invalid cast
                {
                    throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                        $mixItem));
                }

                return $strItem;

            case Type::CALLABLE_TYPE:
                if (is_callable($mixItem)) {
                    return $mixItem;
                } else {
                    throw new InvalidCast(sprintf('Unable to cast %s value to callable', $strOriginalType));
                }

            default:
                throw new InvalidCast(sprintf('Unable to cast %s value to unknown type %s', $strOriginalType,
                    $strNewType));
        }
    }

    public final static function typeFromDoc($strType)
    {
        switch (strtolower($strType)) {
            case 'string':
            case 'str':
                return Type::STRING;

            case 'integer':
            case 'int':
                return Type::INTEGER;

            case 'float':
            case 'flt':
            case 'double':
            case 'dbl':
            case 'single':
            case 'decimal':
                return Type::FLOAT;

            case 'bool':
            case 'boolean':
            case 'bit':
                return Type::BOOLEAN;

            case 'datetime':
            case 'date':
            case 'time':
            case 'qdatetime':
                return Type::DATE_TIME;

            case 'callable':
                return Type::CALLABLE_TYPE;

            case 'null':
            case 'void':
                return 'void';

            default:
                try {
                    new \ReflectionClass($strType);    // cause an exception if we can't do this
                    return $strType;
                } catch (\ReflectionException $objExc) {
                    throw new InvalidCast(sprintf('Unable to determine type of item from PHPDoc Comment to lookup its Type or Class: %s',
                        $strType));
                }
        }
    }

    /**
     * Converts an array to array (without modification) or throws exception
     *
     * @param array $arrItem The array item to be converted
     * @param string $strType Type to which this array has to be converted
     *
     * @return array
     * @throws InvalidCast
     */
    private static function castArrayTo($arrItem, $strType)
    {
        if ($strType == Type::ARRAY_TYPE) {
            return $arrItem;
        } elseif ($strType == Type::CALLABLE_TYPE && is_callable($arrItem)) {
            return $arrItem;
        } else {
            throw new InvalidCast(sprintf('Unable to cast Array to %s', $strType));
        }
    }
    /*
        final public static function soapArrayType($strType) {
            try {
                return sprintf('ArrayOf%s', ucfirst(Type::soapType($strType)));
            } catch (InvalidCast $objExc) {}
                $objExc->incrementOffset();
                throw $objExc;
            }
        }

        final public static function alterSoapComplexTypeArray(&$strComplexTypeArray, $strType) {
            switch ($strType) {
                case Type::STRING:
                    $strItemName = 'string';
                    break;
                case Type::INTEGER:
                    $strItemName = 'int';
                    break;
                case Type::FLOAT:
                    $strItemName = 'float';
                    break;
                case Type::BOOLEAN:
                    $strItemName = 'boolean';
                    break;
                case Type::DATE_TIME:
                    $strItemName = 'dateTime';
                    break;

                case Type::ARRAY_TYPE:
                case Type::OBJECT:
                case Type::RESOURCE:
                default:
                    // Could not determine type
                    throw new InvalidCast(sprintf('Unable to determine type of item to lookup its constant: %s', $strType));
            }

            $strArrayName = Type::soapArrayType($strType);

            if (!array_key_exists($strArrayName, $strComplexTypeArray))
                $strComplexTypeArray[$strArrayName] = sprintf(
                    '<s:complexType name="%s"><s:sequence>' .
                    '<s:element minOccurs="0" maxOccurs="unbounded" name="%s" type="%s"/>' .
                    '</s:sequence></s:complexType>',
                    Type::soapArrayType($strType),
                    $strItemName,
                    Type::soapType($strType));
        }*/
}