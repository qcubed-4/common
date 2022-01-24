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

/**
 * Timer class can help you lightweight profiling of your applications.
 * Use it to measure how long tasks take.
 *
 * If you set the QCUBED_TIMER_OUT_FILE define, the output of your timers will automatically be written
 * to that file after each server access.
 *
 * @author Ago Luberg
 * @was QTimer
 */
class Timer
{
    /**
     * Array of QTime instances
     * @var Timer[]
     */
    protected static $objTimerArray = array();
    /**
     * Name of the timer
     * @var string
     */
    protected $strName;
    /**
     * Total count of timer starts
     * @var int
     */
    protected $intCountStarted = 0;
    /**
     * Timer start time. If -1, then timer is not started
     * @var float
     */
    protected $fltTimeStart = -1;
    /**
     * Timer run time. If timer is stopped, then execution time is kept here
     * @var float
     */
    protected $fltTime = 0;

    /**
     * @param string $strName Timer name
     * @param boolean $blnStart Whether timer is started
     */
    protected function __construct($strName, $blnStart = false)
    {
        $this->strName = $strName;
        if ($blnStart) {
            $this->startTimer();
        }
    }

    /**
     * @return $this
     * @throws Caller
     */
    public function startTimer()
    {
        if ($this->fltTimeStart != -1) {
            throw new Caller("Timer was already started");
        }
        $this->fltTimeStart = microtime(true);
        $this->intCountStarted++;
        return $this;
    }

    /**
     * Starts (new) timer with given name
     * @param string [optional] $strName Timer name
     * @return Timer
     */
    public static function start($strName = 'default')
    {
        $objTimer = static::getTimerInstance($strName);
        return $objTimer->startTimer();
    }

    protected static function getTimerInstance($strName, $blnCreateNew = true)
    {
        if (!isset(static::$objTimerArray[$strName])) {
            if ($blnCreateNew) {
                static::$objTimerArray[$strName] = new Timer($strName);
            } else {
                return null;
            }
        }
        return static::$objTimerArray[$strName];
    }

    /**
     * Gets time from timer with given name
     * @param string [optional] $strName Timer name
     * @return float Timer's time
     * @throws Caller
     */
    public static function getTime($strName = 'default')
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer->getTimerTime();
        } else {
            throw new Caller('Timer with name ' . $strName . ' was not started, cannot get its value');
        }
    }

    /**
     * Returns timer's time
     * @return float Timer's time. If timer is not running, returns saved time.
     */
    public function getTimerTime()
    {
        if ($this->fltTimeStart == -1) {
            return $this->fltTime;
        }
        return $this->fltTime + microtime(true) - $this->fltTimeStart;
    }

    // getters/setters

    // static stuff

    /**
     * Stops time for timer with given name
     * @param string [optional] $strName Timer name
     * @return float Timer's time
     * @throws Caller
     */
    public static function stop($strName = 'default')
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer->stopTimer();
        } else {
            throw new Caller('Timer with name ' . $strName . ' was not started, cannot stop it');
        }
    }

    /**
     * Stops timer. Saves current time for later usage
     * @return float Timer's time
     */
    public function stopTimer()
    {
        $this->fltTime = $this->getTimerTime();
        $this->fltTimeStart = -1;
        return $this->fltTime;
    }

    /**
     * Resets timer with given name
     * @param string [optional] $strName Timer name
     * @return float Timer's time before reset or null if timer does not exist
     */
    public static function reset($strName = 'default')
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer->resetTimer();
        }
        return null;
    }

    /**
     * Resets timer
     * @return float Timer's time before reset
     */
    public function resetTimer()
    {
        $fltTime = $this->stopTimer();
        $this->fltTime = 0;
        $this->startTimer();
        return $fltTime;
    }

    /**
     * Returns timer with a given name
     * @param string [optional] $strName Timer name
     * @return Timer or null if a timer was not found
     */
    public static function getTimer($strName = 'default')
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer;
        }

        return null;
    }

    /**
     * Dumps all the timers and their info
     * @param boolean [optional] $blnDisplayOutput If true (default), dump will be printed. If false, dump will be returned
     * @return string
     */
    public static function varDump($blnDisplayOutput = true)
    {
        $strToReturn = '';
        foreach (static::$objTimerArray as $objTimer) {
            $strToReturn .= $objTimer->__toString() . "\n";
        }
        if ($blnDisplayOutput) {
            echo nl2br($strToReturn);
            return '';
        } else {
            return $strToReturn;
        }
    }

    /**
     * Default toString method for timer
     * @return string
     */
    public function __toString()
    {
        return sprintf("%s - start count: %s - execution time: %f",
            $this->strName,
            $this->intCountStarted,
            $this->getTimerTime());
    }

    // getters/setters?

    public function __get($strName)
    {
        switch ($strName) {
            case 'CountStarted':
                return $this->intCountStarted;
            case 'TimeStart':
                return $this->fltTimeStart;
            default:
                throw new Caller('Invalid property: $strName');
        }
    }
}
