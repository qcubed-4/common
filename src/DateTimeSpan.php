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

require_once (dirname(__DIR__) . '/i18n/i18n-lib.inc.php');

/**
 * Class DateTimeSpan: This class is used to calculate the time difference between two dates (including time)
 *
 * @property-read int $Years   Years in the calculated timespan
 * @property-read int $Months  Months in the calculated timespan
 * @property-read int $Days    Days in the calculated timespan
 * @property-read int $Hours   Hours in the calculated timespan
 * @property-read int $Minutes Minutes in the calculated timespan
 * @property int $Seconds Number seconds which correspond to the time difference
 * @was QDateTimeSpan
 */
class DateTimeSpan extends ObjectBase
{
    /** Number of seconds in an year */
    const SECONDS_PER_YEAR = 31556926;

    /* From: http://tycho.usno.navy.mil/leapsec.html:
        This definition was ratified by the Eleventh General Conference on Weights and Measures in 1960.
        Reference to the year 1900  does not mean that this is the epoch of a mean solar day of 86,400 seconds.
        Rather, it is the epoch of the tropical year of 31,556,925.9747 seconds of ephemeris time.
        Ephemeris Time (ET) was defined as the measure of time that brings the observed positions of the celestial
        bodies into accord with the Newtonian dynamical theory of motion.
    */
    /** Number of seconds in a month (assuming 30 days in a month) */
    const SECONDS_PER_MONTH = 2592000;

    // Assume 30 Days per Month
    /** Number of seconds per day */
    const SECONDS_PER_DAY = 86400;
    /** Number of seconds in an hour */
    const SECONDS_PER_HOUR = 3600;
    /** Number of seconds per minute */
    const SECONDS_PER_MINUTE = 60;
    /** @var int Seconds variable which will be used to calculate the timespan */
    protected $intSeconds;

    /**
     * Constructor for the DateTimeSpan class
     *
     * @param int $intSeconds Number of seconds to set for this DateTimeSpan
     */
    public function __construct($intSeconds = 0)
    {
        $this->intSeconds = $intSeconds;
    }

    /*
        Is functions
    */

    /**
     * Checks if the current DateSpan is zero
     *
     * @return boolean
     */
    public function isZero()
    {
        return ($this->intSeconds == 0);
    }

    /**
     * Calculates the difference between this DateSpan and another DateSpan
     *
     * @param DateTimeSpan $dtsSpan
     * @return DateTimeSpan
     */
    public function difference(DateTimeSpan $dtsSpan)
    {
        $intDifference = $this->Seconds - $dtsSpan->Seconds;
        $dtsDateSpan = new DateTimeSpan();
        $dtsDateSpan->addSeconds($intDifference);
        return $dtsDateSpan;
    }

    /**
     * Adds an amount of seconds to the current DateTimeSpan
     *
     * @param int $intSeconds
     */
    public function addSeconds($intSeconds)
    {
        $this->intSeconds = $this->intSeconds + $intSeconds;
    }

    /**
     * Sets current DateTimeSpan to the difference between two QDateTime objects
     *
     * @param QDateTime $dttFrom
     * @param QDateTime $dttTo
     */
    public function setFromQDateTime(QDateTime $dttFrom, QDateTime $dttTo)
    {
        $this->add($dttFrom->difference($dttTo));
    }

    /*
        SetFrom methods
    */

    /**
     * Adds a DateTimeSpan to current DateTimeSpan
     *
     * @param DateTimeSpan $dtsSpan
     */
    public function add(DateTimeSpan $dtsSpan)
    {
        $this->intSeconds = $this->intSeconds + $dtsSpan->Seconds;
    }

    /*
        Add methods
    */

    /**
     * Adds an amount of minutes to the current DateTimeSpan
     *
     * @param int $intMinutes
     */
    public function addMinutes($intMinutes)
    {
        $this->intSeconds = $this->intSeconds + ($intMinutes * DateTimeSpan::SECONDS_PER_MINUTE);
    }

    /**
     * Adds an amount of hours to the current DateTimeSpan
     *
     * @param int $intHours
     */
    public function addHours($intHours)
    {
        $this->intSeconds = $this->intSeconds + ($intHours * DateTimeSpan::SECONDS_PER_HOUR);
    }

    /**
     * Adds an amount of days to the current DateTimeSpan
     *
     * @param int $intDays
     */
    public function addDays($intDays)
    {
        $this->intSeconds = $this->intSeconds + ($intDays * DateTimeSpan::SECONDS_PER_DAY);
    }

    /**
     * Adds an amount of months to the current DateTimeSpan
     *
     * @param int $intMonths
     */
    public function addMonths($intMonths)
    {
        $this->intSeconds = $this->intSeconds + ($intMonths * DateTimeSpan::SECONDS_PER_MONTH);
    }

    /**
     * Subtracts a DateTimeSpan to current DateTimeSpan
     *
     * @param DateTimeSpan $dtsSpan
     */
    public function subtract(DateTimeSpan $dtsSpan)
    {
        $this->intSeconds = $this->intSeconds - $dtsSpan->Seconds;
    }

    /*
        Get methods
    */

    /**
     * Returns the time difference in approximate duration
     * e.g. "about 4 months" or "4 minutes"
     *
     * The QDateTime class uses this function in its 'Age' property accessor
     *
     * @return null|string
     */
    public function simpleDisplay()
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
     * Return an array of timeunints
     *
     * @return array of timeunits
     */
    protected function getTimearray()
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
    public function isNegative()
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
     * @throws \Exception|Caller
     */

    public function __get($strName)
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
     * Override method to perform a property "Set"
     * This will set the property $strName to be $mixValue
     * PHP magic method
     *
     * @param string $strName Name of the property to set
     * @param string $mixValue New value of the property
     *
     * @return mixed the property that was set
     * @throws \Exception|Caller
     */

    public function __set($strName, $mixValue)
    {
        try {
            switch ($strName) {
                case 'Seconds':
                    return ($this->intSeconds = Type::cast($mixValue, Type::INTEGER));
                default:
                    return (parent::__set($strName, $mixValue));
            }
        } catch (Caller $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }

    /*
        DateMathSettings
    */

    /**
     * Calculates the total whole years in the current DateTimeSpan
     *
     * @return int
     */
    protected function getYears()
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
    public function isPositive()
    {
        return ($this->intSeconds > 0);
    }

    /**
     * Calculates the total whole months in the current DateTimeSpan
     *
     * @return int
     */
    protected function getMonths()
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
     * @return int
     */
    protected function getDays()
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
     * @return int
     */
    protected function getHours()
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
     * @return int
     */
    protected function getMinutes()
    {
        $intSecondsPerMinute = ($this->isPositive()) ? DateTimeSpan::SECONDS_PER_MINUTE : ((-1) * DateTimeSpan::SECONDS_PER_MINUTE);
        $intMinutes = floor($this->intSeconds / $intSecondsPerMinute);
        if ($this->isNegative()) {
            $intMinutes = (-1) * $intMinutes;
        }
        return $intMinutes;
    }
}
