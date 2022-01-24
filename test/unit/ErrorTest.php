<?php

use QCubed\Error\Handler;

/**
 *
 * @package Tests
 */
class ErrorTest extends \QCubed\Test\UnitTestCaseBase
{
    private static $err = null;

    public static function handleError($errNum, $errStr, $errFile, $errLine) {
        self::$err = $errNum;
    }
/*
    public function testNullError()
    {
        error_reporting(E_ALL);
        $e = new Handler();
        $a = BLAH; // should cause an E_NOTICE, but will be ignored
    }*/

    public function testErrHandler() {
        $e = new Handler("ErrorTest::handleError", E_ALL);
        $a = BLAH;

        $this->assertEquals(E_NOTICE, self::$err);
    }

}