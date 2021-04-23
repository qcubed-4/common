<?php

use QCubed\Type;

/**
 *
 * @package Tests
 */

class TypeCastingTest extends \QCubed\Test\UnitTestCaseBase
{
    public function testCasting()
    {
        define('_FAIL_', 'fail');
        $cases = array(
            //array(value, display, expected result, cast to type),
            array("25.0", "25.0", (float)25.0, Type::FLOAT),
            array("25.1", "25.1", (float)25.1, Type::FLOAT),
            array(25.0, "25.0", (float)25.0, Type::FLOAT),
            array(25.1, "25.1", (float)25.1, Type::FLOAT),
            array(true, "true", _FAIL_, Type::FLOAT),
            array("true", "true", _FAIL_, Type::FLOAT),
            array(false, "false", _FAIL_, Type::FLOAT),
            array("false", "false", _FAIL_, Type::FLOAT),
            array(1, "1", (float)1, Type::FLOAT),
            array(0, "0", (float)0, Type::FLOAT),
            array("1", "1", (float)1, Type::FLOAT),
            array("0", "0", (float)0, Type::FLOAT),
            array("25", "25", (float)25, Type::FLOAT),
            array(25, "25", (float)25, Type::FLOAT),
            array(34.51666666667, "34.51666666667", (float)34.51666666667, Type::FLOAT),
            array(2147483648, "2147483648", (float)2147483648, Type::FLOAT),
            array(-2147483648, "-2147483648", (float)-2147483648, Type::FLOAT),
            array(-2147483649, "-2147483649", (float)-2147483649, Type::FLOAT),
            array("34.51666666667", "34.51666666667", (float)34.51666666667, Type::FLOAT),
            array("2147483648", "2147483648", (float)2147483648.0, Type::FLOAT),
            array("-2147483648", "-2147483648", (float)-2147483648.0, Type::FLOAT),
            array("-2147483649", "-2147483649", (float)-2147483649.0, Type::FLOAT),

            array("25.0", "25.0", _FAIL_, Type::INTEGER),
            array("25.1", "25.1", _FAIL_, Type::INTEGER),
            array(25.0, "25.0", (int)25, Type::INTEGER),
            array(25.1, "25.1", _FAIL_, Type::INTEGER),
            array(true, "true", _FAIL_, Type::INTEGER),
            array("true", "true", _FAIL_, Type::INTEGER),
            array(false, "false", _FAIL_, Type::INTEGER),
            array("false", "false", _FAIL_, Type::INTEGER),
            array(1, "1", 1, Type::INTEGER),
            array(0, "0", 0, Type::INTEGER),
            array("1", "1", 1, Type::INTEGER),
            array("0", "0", 0, Type::INTEGER),
            array("25", "25", 25, Type::INTEGER),
            array(25, "25", 25, Type::INTEGER),
            array(34.51666666667, "34.51666666667", _FAIL_, Type::INTEGER),
            array(2147483648, "2147483648", 2147483648, Type::INTEGER),
            array(-2147483648, "-2147483648", (int)-2147483648, Type::INTEGER),
            array(-2147483649, "-2147483649", -2147483649, Type::INTEGER),
            array("34.51666666667", "34.51666666667", _FAIL_, Type::INTEGER),
            array("2147483648", "2147483648", 2147483648, Type::INTEGER),
            array("-2147483648", "-2147483648", (int)-2147483648, Type::INTEGER),
            array("-2147483649", "-2147483649", -2147483649, Type::INTEGER),

            //this number is never stored at full accuracy, so there's no way to tell if it should be
            // an int (perhaps we should force it if it can be?)
            array(1844674407370955161616, "1844674407370955161616", (double)1844674407370955161616, Type::FLOAT),
            //"1844674407370955100000"
            array(1844674407370955161616, "1844674407370955161616", "fail", Type::INTEGER),
            //"1844674407370955100000"

            //this one is
            array("1844674407370955161616", "1844674407370955161616", "1844674407370955161616", Type::FLOAT),
            array("1844674407370955161616", "1844674407370955161616", "1844674407370955161616", Type::INTEGER),

            array(6, '6', '6', Type::STRING),
            array(6.94, '6.94', '6.94', Type::STRING),
            array(0.694 * 10, '6.94', '6.94', Type::STRING),
        );

        foreach ($cases as $case) {
            $value = (string)$case[1] . '(' . gettype($case[0]) . ')';
            if ($case[2] === _FAIL_) {
                $this->setExpectedException('QCubed\\Exception\\InvalidCast');
                Type::cast($case[0], $case[3]);
                $this->setExpectedException(null);
            } else {
                $castValue = Type::cast($case[0], $case[3]);
                $newValue = $castValue . '(' . gettype($castValue) . ')';
                $this->assertTrue($castValue === $case[2], "$value cast as a " . $case[3] . " is $newValue");
            }
        }
    }
}
