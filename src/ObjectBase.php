<?php

/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

use Exception;
use QCubed\Exception\Caller;
use QCubed\Exception\UndefinedMethod;
use QCubed\Exception\UndefinedProperty;
use ReflectionClass;

/**
 * Class ObjectBase
 *
 * This is the Base class for ALL classes in the system.  It provides
 * proper error handling of property getters and setters.  It also
 * provides the overrideAttribute functionality.
 * @package QCubed
 */
abstract class ObjectBase
{
    /**
     * Override method to perform a property "Get"
     * This will get the value of $strName
     * All inherited objects that call __get() should always fall through
     * to calling parent::__get() in a try/catch statement catching
     * for CallerExceptions.
     *
     * @param string $strName Name of the property to get
     *
     * @return mixed the returned property
     * @throws UndefinedProperty
     */
    public function __get(string $strName): mixed
    {
        $objReflection = new ReflectionClass($this);
        throw new UndefinedProperty("GET", $objReflection->getName(), $strName);
    }

    /**
     * Override method to perform a property "Set"
     * This will set the property $strName to be $mixValue
     * All inherited objects that call __set() should always fall through
     * to calling parent::__set() in a try/catch statement catching
     * for CallerExceptions.
     *
     * @param string $strName Name of the property to set
     * @param string $mixValue New value of the property
     *
     * @return void the property that was set
     * @throws UndefinedProperty
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        $objReflection = new ReflectionClass($this);
        throw new UndefinedProperty("SET", $objReflection->getName(), $strName);
    }

    /**
     * Magic method triggered when invoking inaccessible or undefined methods in an object context.
     *
     * @param string $strName The name of the called method.
     * @param array $arguments The arguments passed to the called method.
     *
     * @throws UndefinedMethod When an undefined or inaccessible method is called.
     */
    public function __call(string $strName, array $arguments)
    {
        $objReflection = new ReflectionClass($this);
        throw new UndefinedMethod($objReflection->getName(), $strName);
    }


    /**
     * This allows you to set any properties, given by a name-value pair list
     * in mixOverrideArray.
     * Each item in mixOverrideArray needs to be either a string in the format
     * of Property=Value or an array in the format of array (Property => Value).
     * overrideAttributes() will basically call
     * $this->Property = Value for each string element in the array.
     * Value can be surrounded by quotes... but this is optional.
     *
     * @param array|string $mixOverrideArray
     * @return void
     * @was OverrideAttributes
     *@throws Exception|Caller
     */
    final public function overrideAttributes(array|string $mixOverrideArray): void
    {
        // Iterate through the overrideAttribute Array
        if ($mixOverrideArray) {
            foreach ($mixOverrideArray as $mixOverrideItem) {
                if (is_array($mixOverrideItem)) {
                    foreach ($mixOverrideItem as $strKey => $mixValue) {
                        // Apply the override
                        try {
                            $this->__set($strKey, $mixValue);
                        } catch (Caller $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                    }
                } else {
                    // Extract the Key and Value for this overrideAttribute
                    $intPosition = strpos($mixOverrideItem, "=");
                    if ($intPosition === false) {
                        throw new Caller(sprintf("Improperly formatted overrideAttribute: %s", $mixOverrideItem));
                    }
                    $strKey = substr($mixOverrideItem, 0, $intPosition);
                    $mixValue = substr($mixOverrideItem, $intPosition + 1);

                    // Ensure that the Value is properly formatted (unquoted, single-quoted, or double-quoted)
                    if (str_starts_with($mixValue, "'")) {
                        if (!str_ends_with($mixValue, "'")) {
                            throw new Caller(sprintf("Improperly formatted overrideAttribute: %s", $mixOverrideItem));
                        }
                        $mixValue = substr($mixValue, 1, strlen($mixValue) - 2);
                    } elseif (str_starts_with($mixValue, '"')) {
                        if (!str_ends_with($mixValue, '"')) {
                            throw new Caller(sprintf("Improperly formatted overrideAttribute: %s", $mixOverrideItem));
                        }
                        $mixValue = substr($mixValue, 1, strlen($mixValue) - 2);
                    }

                    // Apply the override
                    try {
                        $this->__set($strKey, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                }
            }
        }
    }
}