<?php

use QCubed\QString;

/**
 *
 * @package Tests
 */
class QStringTest extends \QCubed\Test\UnitTestCaseBase
{

    public function testLongestCommonSubsequence()
    {
        $this->lcsCheckValueHelper("hello world", "world war 2", "world");
        $this->lcsCheckValueHelper("what's up people", "what in the world is going on", "what");
        $this->lcsCheckValueHelper("foo bar", "bar foo", "foo"); // not bar! foo is first!

        $this->lcsCheckValueHelper("aaa", "aa", "aa");
        $this->lcsCheckValueHelper("cc", "bbbbcccccc", "cc");
        $this->lcsCheckValueHelper("ccc", "bcbb", "c");
        $this->lcsCheckValueHelper("aaa", "b", null);
        $this->lcsCheckValueHelper("", "bb", null);
        $this->lcsCheckValueHelper("aa", "", null);
        $this->lcsCheckValueHelper("", null, null);
        $this->lcsCheckValueHelper(null, null, null);
    }

    public function testEndsWithStartsWith()
    {
        $this->assertTrue(QString::startsWith("This is a test", "This"));
        $this->assertFalse(QString::startsWith("This is a test", "this"));
        $this->assertTrue(QString::startsWith("This is a test", "Thi"));
        $this->assertFalse(QString::startsWith("This is a test", "is a"));
        $this->assertFalse(QString::startsWith("This is a test", "X"));
        $this->assertTrue(QString::startsWith("This is a test", ""));

        $this->assertTrue(QString::endsWith("This is a test", "test"));
        $this->assertFalse(QString::endsWith("This is a test", "Test"));
        $this->assertTrue(QString::endsWith("This is a test", "est"));
        $this->assertFalse(QString::endsWith("This is a test", "is a"));
        $this->assertFalse(QString::endsWith("This is a test", "X"));
        $this->assertTrue(QString::endsWith("This is a test", ""));
    }

    private function lcsCheckValueHelper($str1, $str2, $strExpectedResult)
    {
        $strResult = \QCubed\QString::longestCommonSubsequence($str1, $str2);
        $this->assertEquals($strExpectedResult, $strResult, "Longest common subsequence of '" . $str1 .
            "' and '" . $str2 . "' is '" . $strResult . "'");
    }

    public function testTruncate()
    {
        $this->assertEquals('ab...', QString::truncate('abcdefg', 5));
    }

    public function testWordsFromUnderscore()
    {
        $this->assertEquals('I Am Here', QString::wordsFromUnderscore('i_am_here'));
    }

    public function testCamelCaseFromUnderscore()
    {
        $this->assertEquals('IAmHere', QString::camelCaseFromUnderscore('i_am_here'));
    }

    public function testWordsFromCamelCase()
    {
        $this->assertEquals('I Am Here', QString::wordsFromCamelCase('IAmHere'));
    }

    public function testUnderscoreFromCamelCase()
    {
        $this->assertEquals('i_am_here', QString::underscoreFromCamelCase('IAmHere'));
    }


}