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

require_once (dirname(__DIR__) . '/i18n/i18n-lib.inc.php');

/**
 * Class DateTimeSpan: This class is used to calculate the time difference between two dates (including time)
 *
 * @property-read int $Years   Years in the calculated timespan
 * @property-read int $Months  Months in the calculated timespan
 * @property-read int $Days    Days in the calculated timespan
 * @property-read int $Hours   Hours in the calculated timespan
 * @property-read int $Minutes Minutes in the calculated timespan
 * @property int $Seconds Number of seconds which correspond to the time difference
 */
class DateTimeSpan extends ObjectBase
{
    /** Number of seconds in a year */
    const int SECONDS_PER_YEAR = 31556926;

    /* From: http://tycho.usno.navy.mil/leapsec.html:
        This definition was ratified by the Eleventh General Conference on Weights and Measures in 1960.
        Reference to the year 1900 does not mean that this is the epoch of a mean solar day of 86,400 seconds.
        Rather, it is the epoch of the tropical year of 31,556,925.9747 seconds of ephemeris time.
        Ephemeris Time (ET) was defined as the measure of time that brings the observed positions of the celestial
        bodies into accord with the Newtonian dynamical theory of motion.
    */
    /** Number of seconds in a month (assuming 30 days in a month) */
    const int SECONDS_PER_MONTH = 2592000;

    // Assume 30 Days per Month
    /** Number of seconds per day */
    const int SECONDS_PER_DAY = 86400;
    /** Number of seconds in an hour */
    const int SECONDS_PER_HOUR = 3600;
    /** Number of seconds per minute */
    const int SECONDS_PER_MINUTE = 60;
    /** @var int Seconds variable, which will be used to calculate the timespan */
    protected int $intSeconds;

    /**
     * Constructor for the DateTimeSpan class
     *
     * @param int $intSeconds Number of seconds to set for this DateTimeSpan
     */
    public function __construct(int $intSeconds = 0)
    {
        $this->intSeconds = $intSeconds;
    }

    /**
     * Checks if the current DateSpan is zero
     *
     * @return boolean
     */
    public function isZero(): bool
    {
        return ($this->intSeconds == 0);
    }

    /**
     * Calculates the difference between this DateSpan and another DateSpan
     *
     * @param DateTimeSpan $dtsSpan
     * @return DateTimeSpan
     */
    public function difference(DateTimeSpan $dtsSpan): DateTimeSpan
    {
        $intDifference = $this->Seconds - $dtsSpan->Seconds;
        $dtsDateSpan = new DateTimeSpan();
        $dtsDateSpan->addSeconds($intDifference);
        return $dtsDateSpan;
    }

    /**
     * Adds a number of seconds to the current DateTimeSpan
     *
     * @param int $intSeconds
     */
    public function addSeconds(int $intSeconds): void
    {
        $this->intSeconds = $this->intSeconds + $intSeconds;
    }

    /**
     * Sets current DateTimeSpan to the difference between two QDateTime objects
     *
     * @param QDateTime $dttFrom
     * @param QDateTime $dttTo
     */
    public function setFromQDateTime(QDateTime $dttFrom, QDateTime $dttTo): void
    {
        $this->add($dttFrom->difference($dttTo));
    }

    /**
     * Adds a DateTimeSpan to the current DateTimeSpan
     *
     * @param DateTimeSpan $dtsSpan
     */
    public function add(DateTimeSpan $dtsSpan): void
    {
        $this->intSeconds = $this->intSeconds + $dtsSpan->Seconds;
    }

    /**
     * Adds a number of minutes to the current DateTimeSpan
     *
     * @param int $intMinutes
     */
    public function addMinutes(int $intMinutes): void
    {
        $this->intSeconds = $this->intSeconds + ($intMinutes * DateTimeSpan::SECONDS_PER_MINUTE);
    }

    /**
     * Adds a number of hours to the current DateTimeSpan
     *
     * @param int $intHours
     */
    public function addHours(int $intHours): void
    {
        $this->intSeconds = $this->intSeconds + ($intHours * DateTimeSpan::SECONDS_PER_HOUR);
    }

    /**
     * Adds a number of days to the current DateTimeSpan
     *
     * @param int $intDays
     */
    public function addDays(int $intDays): void
    {
        $this->intSeconds = $this->intSeconds + ($intDays * DateTimeSpan::SECONDS_PER_DAY);
    }

    /**
     * Adds a number of months to the current DateTimeSpan
     *
     * @param int $intMonths
     */
    public function addMonths(int $intMonths): void
    {
        $this->intSeconds = $this->intSeconds + ($intMonths * DateTimeSpan::SECONDS_PER_MONTH);
    }

    /**
     * Subtracts a DateTimeSpan to the current DateTimeSpan
     *
     * @param DateTimeSpan $dtsSpan
     */
    public function subtract(DateTimeSpan $dtsSpan): void
    {
        $this->intSeconds = $this->intSeconds - $dtsSpan->Seconds;
    }

    /**
     * Returns the time difference in approximate duration,
     * e.g. "about 4 months" or "4 minutes"
     *
     * The QDateTime class uses this function in its 'Age' property accessor
     *
     * @return null|string
     */
    public function simpleDisplay(): ?string
    {
        $arrTimearray = $this->getTimearray();
        $strToReturn = null;

        if ($arrTimearray['Years'] != 0) {
            $strFormat = tp('a year', 'about %s years', $arrTimearray['Years']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Years']);
        } elseif ($arrTimearray['Months'] != 0) {
            $strFormat = tp('a month', 'about %s months', $arrTimearray['Months']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Months']);
        } elseif ($arrTimearray['Days'] != 0) {
            $strFormat = tp('a day', 'about %s days', $arrTimearray['Days']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Days']);
        } elseif ($arrTimearray['Hours'] != 0) {
            $strFormat = tp('an hour', 'about %s hours', $arrTimearray['Hours']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Hours']);
        } elseif ($arrTimearray['Minutes'] != 0) {
            $strFormat = tp('a minute', '%s minutes', $arrTimearray['Minutes']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Minutes']);
        } elseif ($arrTimearray['Seconds'] != 0) {
            $strFormat = tp('a second', '%s seconds', $arrTimearray['Seconds']);
            $strToReturn = sprintf($strFormat, $arrTimearray['Seconds']);
        }

        return $strToReturn;
    }

    /**
     * Return an array of timeunits
     *
     * @return array of timeunits
     */
    protected function getTimearray(): array
    {
        $intSeconds = abs($this->intSeconds);

        $intYears = floor($intSeconds / DateTimeSpan::SECONDS_PER_YEAR);
        $intSeconds = $intSeconds - ($intYears * DateTimeSpan::SECONDS_PER_YEAR);

        $intMonths = floor($intSeconds / DateTimeSpan::SECONDS_PER_MONTH);
        $intSeconds = $intSeconds - ($intMonths * DateTimeSpan::SECONDS_PER_MONTH);

        $intDays = floor($intSeconds / DateTimeSpan::SECONDS_PER_DAY);
        $intSeconds = $intSeconds - ($intDays * DateTimeSpan::SECONDS_PER_DAY);

        $intHours = floor($intSeconds / DateTimeSpan::SECONDS_PER_HOUR);
        $intSeconds = $intSeconds - ($intHours * DateTimeSpan::SECONDS_PER_HOUR);

        $intMinutes = floor($intSeconds / DateTimeSpan::SECONDS_PER_MINUTE);
        $intSeconds = $intSeconds - ($intMinutes * DateTimeSpan::SECONDS_PER_MINUTE);

        if ($this->isNegative()) {
            // Turn values to negative
            $intYears = ((-1) * $intYears);
            $intMonths = ((-1) * $intMonths);
            $intDays = ((-1) * $intDays);
            $intHours = ((-1) * $intHours);
            $intMinutes = ((-1) * $intMinutes);
            $intSeconds = ((-1) * $intSeconds);
        }

        return array(
            'Years' => $intYears,
            'Months' => $intMonths,
            'Days' => $intDays,
            'Hours' => $intHours,
            'Minutes' => $intMinutes,
            'Seconds' => $intSeconds
        );
    }

    /**
     * Checks if the current DateSpan is negative
     *
     * @return boolean
     */
    public function isNegative(): bool
    {
        return ($this->intSeconds < 0);
    }

    /**
     * Override method to perform a property "Get"
     * This will get the value of $strName
     * PHP magic method
     *
     * @param string $strName Name of the property to get
     *
     * @return mixed the returned property
     * @throws Exception
     */

    public function __get(string $strName): mixed
    {
        switch ($strName) {
            case 'Years':
                return $this->getYears();
            case 'Months':
                return $this->getMonths();
            case 'Days':
                return $this->getDays();
            case 'Hours':
                return $this->getHours();
            case 'Minutes':
                return $this->getMinutes();
            case 'Seconds':
                return $this->intSeconds;
            case 'Timearray':
                return ($this->getTimearray());

            default:
                try {
                    return parent::__get($strName);
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
        }
    }


    /**
     * Sets the value of a property.
     *
     * @param string $strName The name of the property.
     * @param mixed $mixValue The value to set for the property.
     *
     * @return void Returns the value assigned to the property.
     * @throws Caller If the property name is invalid.
     * @throws InvalidCast
     * @throws Exception
     */
    public function __set(string $strName, mixed $mixValue): void
    {
        try {
            switch ($strName) {
                case 'Seconds':
                    $this->intSeconds = Type::cast($mixValue, Type::INTEGER);
                    break;
                default:
                    parent::__set($strName, $mixValue);
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /**
     * Calculates the total whole years in the current DateTimeSpan
     *
     * @return float|int
     */
    protected function getYears(): float|int
    {
        $intSecondsPerYear = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_YEAR : ((-1) * DateTimeSpan::SECONDS_PER_YEAR);
        $intYears = floor($this->intSeconds / $intSecondsPerYear);
        if ($this->isNegative()) {
            $intYears = (-1) * $intYears;
        }
        return $intYears;
    }

    /**
     * Checks if the current DateSpan is positive
     *
     * @return boolean
     */
    public function isPositive(): bool
    {
        return ($this->intSeconds > 0);
    }

    /**
     * Calculates the total whole months in the current DateTimeSpan
     *
     * @return float|int
     */
    protected function getMonths(): float|int
    {
        $intSecondsPerMonth = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_MONTH : ((-1) * DateTimeSpan::SECONDS_PER_MONTH);
        $intMonths = floor($this->intSeconds / $intSecondsPerMonth);
        if ($this->isNegative()) {
            $intMonths = (-1) * $intMonths;
        }
        return $intMonths;
    }

    /**
     * Calculates the total whole days in the current DateTimeSpan
     *
     * @return float|int
     */
    protected function getDays(): float|int
    {
        $intSecondsPerDay = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_DAY : ((-1) * DateTimeSpan::SECONDS_PER_DAY);
        $intDays = floor($this->intSeconds / $intSecondsPerDay);
        if ($this->isNegative()) {
            $intDays = (-1) * $intDays;
        }
        return $intDays;
    }

    /**
     * Calculates the total whole hours in the current DateTimeSpan
     *
     * @return float|int
     */
    protected function getHours(): float|int
    {
        $intSecondsPerHour = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_HOUR : ((-1) * DateTimeSpan::SECONDS_PER_HOUR);
        $intHours = floor($this->intSeconds / $intSecondsPerHour);
        if ($this->isNegative()) {
            $intHours = (-1) * $intHours;
        }
        return $intHours;
    }

    /**
     * Calculates the total whole minutes in the current DateTimeSpan
     *
     * @return float|int
     */
    protected function getMinutes(): float|int
    {
        $intSecondsPerMinute = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_MINUTE : ((-1) * DateTimeSpan::SECONDS_PER_MINUTE);
        $intMinutes = floor($this->intSeconds / $intSecondsPerMinute);
        if ($this->isNegative()) {
            $intMinutes = (-1) * $intMinutes;
        }
        return $intMinutes;
    }
}