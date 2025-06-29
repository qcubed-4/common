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
    use Exception;
    use ReflectionClass;
    use ReflectionException;

    /**
     * Type Library to add some support for strongly named types.
     *
     * PHP does not support strongly named types.  The QCubed type library
     * and QCubed typing in a general attempt to bring some structure to types
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
     * In addition to value objects (into, books, floats, strings), the Type library
     * also supports object casting.  While technically casting one object to another
     * is not a true cast, Type::Cast does at least ensure that the tap being "cast"
     * to is a legitimate subclass of the object being "cast".  So if you have ParentClass,
     * and you have a ChildClass that extends ParentClass,
     *        $objChildClass = new ChildClass();
     *        $objParentClass = new ParentClass();
     *        Type::cast($objChildClass, 'ParentClass'); // is a legal cast
     *        Type::cast($objParentClass, 'ChildClass'); // will throw an InvalidCastException
     *
     * For values, specifically int to string conversion, one difference between
     * Type::Cast and PHP (in order to add structure) is that if an integer contains
     * alpha characters, PHP would normally allow that through w/o complaint, simply
     * ignoring any numeric characters past the first alpha character.  Type::Cast
     * would instead throw an InvalidCastException to let the developer immediately
     * know that something doesn't look right.
     *
     * In theory, the type library should maintain the same level of flexibility
     * PHP developers are accustomed to, while providing a mechanism to limit
     * careless coding errors and tough to figure out mistakes due to PHP's sometimes
     * overly taxed type conversions.
     */
    class Type
    {
        public const STRING = 'string';
        public const INTEGER = 'integer';
        public const FLOAT = 'double';
        public const BOOLEAN = 'boolean';
        public const OBJECT = 'object';
        public const ARRAY_TYPE = 'array';
        public const DATE_TIME = '\\QCubed\\QDateTime';
        public const RESOURCE = 'resource';
        public const CALLABLE_TYPE = 'callable'; // Callable Type - Note: For QCubed, Type::CALLABLE_TYPEs CANNOT be Closures (because they cannot be serialized into the form state)
        public const ASSOCIATION = 'association';

        // Virtual types
        const REVERSE_REFERENCE = 'reverse_reference';
        const NO_OP = 1;
        const CHECK_ONLY = 2;
        const CAST_ONLY = 3;
        const CHECK_AND_CAST = 4;
        private static int $intBehaviour = Type::CHECK_AND_CAST;

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
            throw new Caller('Type should never be instantiated.  All methods and variables are publicly statically accessible.');
        }

        /**
         * This method can be used to change the casting behaviour of Type::cast().
         * By default, Type::cast() does lots of validation and type casting (using style()).
         * Depending on your application, you may or may not need validation or casting or both.
         * In these situations, you can set the necessary behaviour by passing the appropriate constant to this function.
         *
         * @static
         *
         * @param int $intBehaviour one of the 4 constants Type::NO_OP, Type::CAST_ONLY, Type::CHECK_ONLY, Type::CHECK_AND_CAST
         *
         * @return int the previous setting
         */
        public static function setBehaviour(int $intBehaviour): int
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
        public final static function constant(string $strType): string
        {
            return match ($strType) {
                Type::OBJECT => 'QCubed\\Type::OBJECT',
                Type::STRING => 'QCubed\\Type::STRING',
                Type::INTEGER => 'QCubed\\Type::INTEGER',
                Type::FLOAT => 'QCubed\\Type::FLOAT',
                Type::BOOLEAN => 'QCubed\\Type::BOOLEAN',
                Type::ARRAY_TYPE => 'QCubed\\Type::ARRAY_TYPE',
                Type::RESOURCE => 'QCubed\\Type::RESOURCE',
                Type::DATE_TIME => 'QCubed\\Type::DATE_TIME',
                default => throw new InvalidCast(sprintf('Unable to determine a type of item to look up its constant: %s', $strType)),
            };
        }

        /**
         * Used by the QCubed Code Generator and QSoapService class to allow for the XML generation of
         * the actual "s:type" Soap Variable types.
         *
         * @param string $strType the type to convert to 'constant' form
         *
         * @return string the text of the SOAP standard s:type variable type
         * @throws InvalidCast
         */
        public final static function soapType(string $strType): string
        {
            return match ($strType) {
                Type::STRING => 'string',
                Type::INTEGER => 'int',
                Type::FLOAT => 'float',
                Type::BOOLEAN => 'boolean',
                Type::DATE_TIME => 'dateTime',
                default => throw new InvalidCast(sprintf('Unable to determine a type of item to look up its constant: %s', $strType)),
            };
        }

        /**
         * Casts an object to a specified type if possible. Handles specific cases
         * for SimpleXMLElement, Closure, and other object types. Throw an exception
         * if the casting is not possible.
         *
         * @param mixed $objItem The object to be cast.
         * @param string $strType The desired type to cast the object to.
         *
         * @return mixed Returns the object cast to the specified type or throws an exception if it fails.
         * @throws InvalidCast If the object cannot be cast to the specified type.
         *
         * @throws Exception If a Closure is used, it cannot be applied.
         */
        private static function castObjectTo(mixed $objItem, string $strType): mixed
        {
            $objReflection = '';
            try {
                $objReflection = new ReflectionClass($objItem);
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
                        throw new Exception("Can't use a closure here"); // Will get rethrown below, but this will error to
                        // prevent you from accidentally sending a Closure to a callable in a form object.
                        // That cannot be done because Closures are not serializable. Some other forms of
                        // callables ARE serializable, though, so use that instead.
                    }
                }

                if ($objItem instanceof $strType) {
                    return $objItem;
                }

                if ($strType == Type::STRING) {
                    return (string)$objItem;    // invokes __toString() magic method
                }
            } catch (Exception $objExc) {
            }

            throw new InvalidCast(sprintf('Unable to cast %s object to %s', $objReflection->getName(), $strType));
        }

        /**
         * Used to cast a variable to another type.  Allows for moderate
         * support of strongly named types.
         * Will throw an exception if the cast fails, causes unexpected side effects,
         * if attempting to cast an object to a value (or vice versa), or if an object
         * is being cast to a class that isn't a subclass (e.g., parent).  The exception
         * thrown will be an InvalidCastException, which extends CallerException.
         *
         * @param mixed $mixItem the value, array or object that you want to cast
         * @param string $strType the type to cast to.  Can be a Type::XXX constant (e.g., Type::INTEGER), or the name of a Class
         *
         * @return mixed the passed in value/array/object that has been cast to strType
         * @throws Exception|Caller|InvalidCast
         */
        public final static function cast(mixed $mixItem, string $strType): mixed
        {
            switch (Type::$intBehaviour) {
                case Type::NO_OP:
                    return $mixItem;
                case Type::CAST_ONLY:
                    throw new Caller("Type::CAST_ONLY handling not yet implemented");
                case Type::CHECK_ONLY:
                    throw new Caller("Type::CHECK_ONLY handling not yet implemented");
                case Type::CHECK_AND_CAST:
                    break;
                default:
                    throw new Exception('Unknown Type behavior');
            }
            // Automatically Return NULLs
            if (is_null($mixItem)) {
                return null;
            }

            // Figure out what PHP thinks the type is
            $strPhpType = gettype($mixItem);

            switch ($strPhpType) {
                case 'object':
                    try {
                        return Type::castObjectTo($mixItem, $strType);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                case 'string':
                case 'integer':
                case 'double':
                case 'boolean':
                    try {
                        return Type::castValueTo($mixItem, $strType);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                case 'array':
                    try {
                        return Type::castArrayTo($mixItem, $strType);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                case 'resource':
                    // Cannot Cast Resources
                    throw new InvalidCast('Resources cannot be cast');

                default:
                    // Could not determine type
                    throw new InvalidCast(sprintf('Unable to determine a type of item to be cast: %s', $mixItem));
            }
        }

        /**
         * Casts a given value to a specified type. Depending on the original value's type and the desired type,
         * this method attempts to perform the conversion while ensuring the integrity and compatibility of the
         * value throughout the casting process. If a conversion is deemed invalid or impossible, an exception
         * is thrown.
         *
         * @param mixed $mixItem The value to be cast to the specified type.
         * @param string $strNewType The target type to which the value should be cast, defined as a string.
         *
         * @return mixed Returns a value of the specified type or an initial value that is below certain
         *               conditions where casting is not required or applicable.
         * @throws InvalidCast If the value cannot be safely cast to the specified type or if the type is invalid.
         */
        private static function castValueTo(mixed $mixItem, string $strNewType): mixed
        {
            $strOriginalType = gettype($mixItem);

            switch (Type::typeFromDoc($strNewType)) {
                case Type::BOOLEAN:
                    if ($strOriginalType == 'boolean') {
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

                case 'integer':
                    if ($strOriginalType == 'boolean') {
                        throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                            $mixItem));
                    }
                    if (strlen($mixItem) == 0) {
                        return null;
                    }
                    if ($strOriginalType == 'integer') {
                        return $mixItem;
                    }

                    // Check to make sure the value hasn't changed significantly
                    $intItem = $mixItem;
                    settype($intItem, $strNewType);
                    $mixTest = $intItem;
                    settype($mixTest, $strOriginalType);

                    // If the value hasn't changed, it's safe to return the cast value
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
                    if ($strOriginalType == 'boolean') {
                        throw new InvalidCast(sprintf('Unable to cast %s value to %s: %s', $strOriginalType, $strNewType,
                            $mixItem));
                    }
                    if (strlen($mixItem) == 0) {
                        return null;
                    }
                    if ($strOriginalType == 'double') {
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
                    //and represent the cast value the same way
                    $strTest = sprintf('%.' . $precision . 'f', $fltItem);

                    // If the value hasn't changed, it's safe to return the cast value
                    if ($strTest === (string)$mixItem) {
                        return $fltItem;
                    }

                    // The changed value could be the result of losing precision. Return the original value with no cast
                    return $mixItem;

                case Type::STRING:
                    if ($strOriginalType == 'string') {
                        return $mixItem;
                    }

                    // Check to make sure the value hasn't changed significantly
                    $strItem = $mixItem;
                    settype($strItem, $strNewType);
                    $mixTest = $strItem;
                    settype($mixTest, $strOriginalType);

                    // Has it?
                    $blnSame = true;
                    if ($strOriginalType == 'double') {
                        // type conversion from float to string affects precision and can throw off the comparison,
                        // so we need to use a comparison check using an epsilon value instead of
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

        /**
         * Determines the type or class based on a PHPDoc-style type string.
         *
         * @param string $strType The type string extracted from the PHPDoc comment.
         *                        Examples include 'string', 'integer', 'float', 'datetime', 'void', etc.
         *
         * @return string Returns a constant from the Type class, 'void' for null or void types, or the original class name.
         *               Throw an exception if the type cannot be determined or resolved.
         * @throws InvalidCast If the type string cannot be mapped to a valid Type constant or existing class.
         */
        public final static function typeFromDoc(string $strType): string
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
                        new ReflectionClass($strType);    // cause an exception if we can't do this
                        return $strType;
                    } catch (ReflectionException $objExc) {
                        throw new InvalidCast(sprintf('Unable to determine a type of item from PHPDoc Comment to look up its Type or Class: %s',
                            $strType));
                    }
            }
        }

        /**
         * Casts an array item to the specified type if possible.
         *
         * @param mixed $arrItem The array item to cast.
         * @param string $strType The target type to cast the array item to.
         *                        For example, Type::ARRAY_TYPE or Type::CALLABLE_TYPE.
         *
         * @return mixed Returns the cast array item if it matches the specified type.
         *
         * @throws InvalidCast If the array item cannot be cast to the specified type.
         */
        private static function castArrayTo(mixed $arrItem, string $strType): mixed
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
                        throw new InvalidCast(sprintf('Unable to determine a type of item to look up its constant: %s', $strType));
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
