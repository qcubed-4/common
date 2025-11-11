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
 * Timer class can help you with lightweight profiling of your applications.
 * Use it to measure how long tasks take.
 *
 * If you set the QCUBED_TIMER_OUT_FILE definition, the output of your timers will automatically be written
 * to that file after each server access.
 *
 * @author Ago Luberg
 */
class Timer
{
    /**
     * Array of QTime instances
     * @var Timer[]
     */
    protected static array $objTimerArray = array();
    /**
     * Name of the timer
     * @var string
     */
    protected string $strName;
    /**
     * Total count of timer starts
     * @var int
     */
    protected int $intCountStarted = 0;
    /**
     * Timer start time. If -1, then the timer is not started
     * @var int|float
     */
    protected int|float $fltTimeStart = -1;
    /**
     * Timer run time. If the timer is stopped, then execution time is kept here
     * @var int|float
     */
    protected int|float $fltTime = 0;

    /**
     * @param string $strName Timer name
     * @param boolean $blnStart Whether a timer is started
     * @throws Caller
     */
    protected function __construct(string $strName, ?bool $blnStart = false)
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
    public function startTimer(): static
    {
        if ($this->fltTimeStart != -1) {
            throw new Caller("Timer was already started");
        }
        $this->fltTimeStart = microtime(true);
        $this->intCountStarted++;
        return $this;
    }

    /**
     * Starts the timer with the given name.
     * @param string $strName [optional] Timer name
     * @return Timer Instance of the started timer
     * @throws Caller
     */
    public static function start(string $strName = 'default'): Timer
    {
        $objTimer = static::getTimerInstance($strName);
        return $objTimer->startTimer();
    }

    /**
     * Retrieves an instance of a Timer object by name. If the Timer does not exist,
     * a new one can be created based on the provided parameter.
     *
     * @param string $strName The name of the Timer instance to retrieve.
     * @param bool $blnCreateNew Whether to create a new Timer instance if it does not already exist.
     * @return Timer|null The Timer instance associated with the provided name, or null if it does not exist and creation is not allowed.
     * @throws Caller
     */
    protected static function getTimerInstance(string $strName, bool $blnCreateNew = true): ?Timer
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
     * Gets time from a timer with a given name
     * @param string $strName [optional] $strName Timer name
     * @return float|int Timer's time
     * @throws Caller
     */
    public static function getTime(string $strName = 'default'): float|int
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
     * @return float|int Timer's time. If the timer is not running, returns saved time.
     */
    public function getTimerTime(): float|int
    {
        if ($this->fltTimeStart == -1) {
            return $this->fltTime;
        }
        return $this->fltTime + microtime(true) - $this->fltTimeStart;
    }

    /**
     * Stops time for a timer with a given name
     * @param string $strName [optional] $strName Timer name
     * @return float|int Timer's time
     * @throws Caller
     */
    public static function stop(string $strName = 'default'): float|int
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer->stopTimer();
        } else {
            throw new Caller('Timer with name ' . $strName . ' it was not started, cannot stop it');
        }
    }

    /**
     * Stops timer. Saves current time for later usage
     * @return float|int Timer's time
     */
    public function stopTimer(): float|int
    {
        $this->fltTime = $this->getTimerTime();
        $this->fltTimeStart = -1;
        return $this->fltTime;
    }

    /**
     * Resets timer with given name
     * @param string $strName [optional] $strName Timer name
     * @return float|int|null Timer's time before reset or null if timer does not exist
     * @throws Caller
     */
    public static function reset(string $strName = 'default'): float|int|null
    {
        $objTimer = static::getTimerInstance($strName, false);
        return $objTimer?->resetTimer();
    }

    /**
     * Resets timer
     * @return float|int Timer's time before reset
     * @throws Caller
     */
    public function resetTimer(): float|int
    {
        $fltTime = $this->stopTimer();
        $this->fltTime = 0;
        $this->startTimer();
        return $fltTime;
    }

    /**
     * Returns timer with a given name
     * @param string $strName [optional] $strName Timer name
     * @return Timer|null or null if a timer was not found
     * @throws Caller
     */
    public static function getTimer(string $strName = 'default'): ?Timer
    {
        $objTimer = static::getTimerInstance($strName, false);
        if ($objTimer) {
            return $objTimer;
        }

        return null;
    }

    /**
     * Dumps all the timers and their info
     * @param boolean $blnDisplayOutput [optionally] $blnDisplayOutput If true (default), the dump will be printed. If false, the dump will be returned
     * @return string
     */
    public static function varDump(bool $blnDisplayOutput = true): string
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
     * Converts the Timer object to its string representation, including its name,
     * start count, and total execution time.
     *
     * @return string A formatted string containing the Timer's name, start count, and execution time.
     */
    public function __toString(): string
    {
        return sprintf("%s - start count: %s - execution time: %f",
            $this->strName,
            $this->intCountStarted,
            $this->getTimerTime());
    }

    /**
     * Magic method to retrieve the value of a property based on its name.
     * Delegates to the parent `__get` method if the property is not found
     * in the current class and handles exceptions accordingly.
     *
     * @param string $strName The name of the property to retrieve.
     *
     * @return float|int|string The value of the property.
     * @throws Caller If the requested property does not exist.
     */
    public function __get(string $strName): mixed
    {
        return match ($strName) {
            'CountStarted' => $this->intCountStarted,
            'TimeStart' => $this->fltTimeStart,
            default => throw new Caller("Undefined property: " . $strName),
        };
    }

}