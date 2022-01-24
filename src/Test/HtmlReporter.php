<?php
/**
 * MIT License
 *
 * Copyright (c) Shannon Pekary spekary@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace QCubed\Test;


/**
 * HtmlReporter for local test case running. Saves the output in to a session variable so that our output displayer
 * can display it.
 *
 * Class HtmlReporter
 * @package QCubed\Test
 */
class HtmlReporter extends \PHPUnit_TextUI_ResultPrinter
{
    protected $results;
    protected $currentSuite;
    protected $currentTest;
    protected $currentGroup;

    public function __construct(
        $out = null,
        $verbose = false,
        $colors = self::COLOR_DEFAULT,
        $debug = false,
        $columns = 80,
        $reverseList = false
    ) {
        parent::__construct($out, $verbose, $colors, $debug, $columns, $reverseList);
    }


    public function write($buffer)
    {
        // noop this
    }


    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->currentSuite = $suite->getName();
        if ($this->currentSuite  &&
            ($tests = $suite->tests()) &&
            $tests[0] instanceof \PHPUnit_Framework_TestSuite
        ) {
            // is actually a test group
            $this->currentGroup = $this->currentSuite;
            $this->currentSuite = null;
        }
        elseif ($this->currentGroup) {
            $this->currentSuite = $this->currentGroup . ':' . $this->currentSuite;
        }
    }

    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->currentSuite = null;
    }


    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $this->currentTest = $test->getName();
        $this->results[$this->currentSuite][$test->getName()]['test'] = $test;
    }

    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $this->results[$this->currentSuite][$test->getName()]['status'] = 'error';
        $this->results[$this->currentSuite][$test->getName()]['errors'][] = compact('e', 'time');
    }

    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $this->results[$this->currentSuite][$test->getName()]['status'] = 'failed';
        $this->results[$this->currentSuite][$test->getName()]['results'][] = compact('e', 'time');
    }

    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $this->results[$this->currentSuite][$test->getName()]['status'] = 'incomplete';
        $this->results[$this->currentSuite][$test->getName()]['errors'][] = compact('e', 'time');
    }

    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time)
    {
        $this->results[$this->currentSuite][$test->getName()]['status'] = 'skipped';
        $this->results[$this->currentSuite][$test->getName()]['errors'][] = compact('e', 'time');
    }

    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $t = &$this->results[$this->currentSuite][$test->getName()];
        if (!isset($t['status'])) {
            $t['status'] = 'passed';
        }
        $t['time'] = $time;
        $this->currentTest = null;
    }

    public function printResult(\PHPUnit_Framework_TestResult $result)
    {
        $strHtml = '';

        foreach ($this->results as $suiteName => $suite) {
            $strHtml.= "<b>$suiteName</b><br />";
            foreach ($suite as $testName => $test) {
                $status = $test['status'];
                $status = ucfirst($status);
                if ($test['status'] !== 'passed') {
                    $status = '<span style="color:red">' . $status . '</span>';
                } else {
                    $status = '<span style="color:green">' . $status . '</span>';
                }

                $strHtml .= "$status: $testName";
                $strHtml = "$strHtml<br />";
                if (isset($test['errors'])) {
                    foreach ($test['errors'] as $error) {
                        $strHtml .= nl2br(htmlentities($error['e']->__toString())) . '<br />';
                    }
                }
                if (isset($test['results'])) {
                    foreach ($test['results'] as $error) {
                        $strMessage = $error['e']->__toString() . "\n";
                        // get first line
                        $lines = explode("\n", \PHPUnit_Util_Filter::getFilteredStacktrace($error['e']));
                        $strMessage .= $lines[0] . "\n";
                        $strHtml .= nl2br(htmlentities($strMessage)) . '<br />';
                    }
                }
            }
        }

        $str = "\nRan " . $result->count() . " tests in " . $result->time() . " seconds.\n";
        $str .= $result->failureCount() . " assertions failed.\n";
        $str .= $result->errorCount() . " exceptions were thrown.\n";
        $strHtml .= nl2br($str);

        $_SESSION['HtmlReporterOutput'] .= $strHtml;
    }
}
