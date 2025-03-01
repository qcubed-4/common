<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

// These Aid with the PHP 5.2 QDateTime error handling
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use QCubed\Exception\UndefinedProperty;
use QCubed\Common\t;

require_once (dirname(__DIR__) . '/i18n/i18n-lib.inc.php');

/**
 * QDateTime
 * This QDateTime class provides a nice wrapper around the PHP QDateTime class,
 * which is included with all versions of PHP >= 5.2.0. It includes many enhancements,
 * including the ability to specify a null date or time portion to represent a date only or
 * time only object.
 *
 * Inherits from the php DateTime object, and the built-in methods are available for you to call
 * as well. In particular, note that the built-in format, and the qFormat routines here take different
 * specifiers. Feel free to use either.
 *
 * Note: QDateTime was kept as the name to avoid potential naming confusion and collisions with the built-in DateTime name.
 *
 * @property null|integer $Month
 * @property null|integer $Day
 * @property null|integer $Year
 * @property null|integer $Hour
 * @property null|integer $Minute
 * @property null|integer $Second
 * @property integer $Timestamp
 * @property-read string $Age                A string representation of the age relative to now.
 * @property-read QDateTime $LastDayOfTheMonth  A new QDateTime representing the last day of this date's month.
 * @property-read QDateTime $FirstDayOfTheMonth A new QDateTime representing the first day of this date's month.
 * @was QDateTime
 */
class QDateTime extends \DateTime implements \JsonSerializable, \Serializable
{
    /** Used to specify the time right now (used when creating new instances of this class) */
    const NOW = 'now';

    /** These formatters are for the qFormat function */
    const FORMAT_ISO = 'YYYY-MM-DD hhhh:mm:ss'; // Date and time in ISO format
    const FORMAT_ISO_COMPRESSED = 'YYYYMMDDhhhhmmss'; //Date and time in ISO compressed format
    const FORMAT_DISPLAY_DATE = 'MMM DD YYYY'; //Format used for displaying short date
    const FORMAT_DISPLAY_DATE_FULL = 'DDD, MMMM D, YYYY'; //Format used for displaying the full date
    const FORMAT_DISPLAY_DATE_TIME = 'MMM DD YYYY hh:mm zz'; //Format used for displaying the short date and time
    const FORMAT_DISPLAY_DATE_TIME_FULL = 'DDDD, MMMM D, YYYY, h:mm:ss zz'; //Format used for displaying the full date and time
    const FORMAT_DISPLAY_TIME = 'hh:mm:ss zz'; //Format to display only the time
    const FORMAT_RFC_822 = 'DDD, DD MMM YYYY hhhh:mm:ss ttt'; //Date and time format according to RFC 822
    const FORMAT_RFC_5322 = 'DDD, DD MMM YYYY hhhh:mm:ss ttttt'; //Date and time format according to RFC 5322
    const FORMAT_SOAP = 'YYYY-MM-DDThhhh:mm:ss'; //Format used to represent date for SOAP

    /** Note that you can also call the inherited format() function with the following built-in constants */
    /*
    const ATOM = 'Y-m-d\TH:i:sP';
    const COOKIE = 'l, d-M-y H:i:s T';
    const ISO8601 = 'Y-m-d\TH:i:sO';
    const RFC822 = 'D, d M y H:i:s O';
    const RFC850 = 'l, d-M-y H:i:s T';
    const RFC1036 = 'D, d M y H:i:s O';
    const RFC1123 = 'D, d M Y H:i:s O';
    const RFC2822 = 'D, d M Y H:i:s O';
    const RFC3339 = 'Y-m-d\TH:i:sP';
    const RSS = 'D, d M Y H:i:s O';
    const W3C = 'Y-m-d\TH:i:sP';
    */

    /* Type in which the date and time has to be interpreted */
    const UNKNOWN_TYPE = 0;
    const DATE_ONLY_TYPE = 1;
    const TIME_ONLY_TYPE = 2;
    const DATE_AND_TIME_TYPE = 3;

    /** @var bool true if date is null */
    protected $blnDateNull = true;
    /** @var bool  true if time is null, rather than just zero (beginning of day) */
    protected $blnTimeNull = true;


    /**
     * The "Default" Display Format
     * @var string $DefaultFormat
     */
    public static $DefaultFormat = QDateTime::FORMAT_DISPLAY_DATE_TIME;

    /**
     * The "Default" Display Format for Times
     * @var string $DefaultTimeFormat
     */
    public static $DefaultTimeFormat = QDateTime::FORMAT_DISPLAY_TIME;

    /**
     * The "Default" Display Format for Dates with null times
     * @var string $DefaultDateOnlyFormat
     */
    public static $DefaultDateOnlyFormat = QDateTime::FORMAT_DISPLAY_DATE;


    /**
     * Returns a new QDateTime object that's set to "Now"
     * Set blnTimeValue to true (default) for a QDateTime, and set blnTimeValue to false for just a Date
     *
     * @param boolean $blnTimeValue whether or not to include the time value
     * @return QDateTime the current date and/or time
     */
    public static function now($blnTimeValue = true)
    {
        $dttToReturn = new QDateTime(QDateTime::NOW);
        if (!$blnTimeValue) {
            $dttToReturn->blnTimeNull = true;
            $dttToReturn->reinforceNullProperties();
        }
        return $dttToReturn;
    }

    /**
     * Return Now as a string. Uses the default datetime format if none speicifed.
     * @param string|null $strFormat
     * @return string
     */
    public static function nowToString($strFormat = null)
    {
        $dttNow = new QDateTime(QDateTime::NOW);
        return $dttNow->qFormat($strFormat);
    }

    /**
     * @return bool
     */
    public function isDateNull()
    {
        return $this->blnDateNull;
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return ($this->blnDateNull && $this->blnTimeNull);
    }

    /**
     * @return bool
     */
    public function isTimeNull()
    {
        return $this->blnTimeNull;
    }

    /**
     * @param $strFormat
     * @return string
     */
    public function phpDate($strFormat)
    {
        // This just makes a call to format
        return parent::format($strFormat);
    }

    /**
     * @param QDateTime[] $dttArray
     * @return array
     */
    public function getSoapDateTimeArray($dttArray)
    {
        if (!$dttArray) {
            return null;
        }

        $strArrayToReturn = array();
        foreach ($dttArray as $dttItem) {
            array_push($strArrayToReturn, $dttItem->qFormat(QDateTime::FORMAT_SOAP));
        }
        return $strArrayToReturn;
    }

    /**
     * Create from a unix timestamp. Improves over php by taking into consideration the
     * timezone, so that the internal format is automatically converted to the internal timezone,
     * or the default timezone.
     *
     * @param integer $intTimestamp
     * @param \DateTimeZone $objTimeZone
     * @return QDateTime
     */
    public static function fromTimestamp($intTimestamp, ?\DateTimeZone $objTimeZone = null)
    {
        return new QDateTime(date('Y-m-d H:i:s', $intTimestamp), $objTimeZone);
    }

    /**
     * Construct a QDateTime. Does a few things differently than the php version:
     * - Always stores timestamps in local or given timezone, so time extraction is easy
     * - Has settings to determine if you want a date only or time only type
     * - Will NOT throw exceptions. Errors simply result in a null datetime.
     *
     * @param null|integer|string|QDateTime|QDateTime $mixValue
     * @param \DateTimeZone $objTimeZone
     * @param int $intType
     *
     * @throws Caller
     */
    public function __construct($mixValue = null, ?\DateTimeZone $objTimeZone = null, $intType = QDateTime::UNKNOWN_TYPE)
    {
        if ($mixValue instanceof QDateTime) {
            // Cloning from another QDateTime object
            if ($objTimeZone) {
                throw new Caller('QDateTime cloning cannot take in a DateTimeZone parameter');
            }
            parent::__construct($mixValue->format('Y-m-d H:i:s'), $mixValue->getTimeZone());
            $this->blnDateNull = $mixValue->isDateNull();
            $this->blnTimeNull = $mixValue->isTimeNull();
            $this->reinforceNullProperties();
        } else {
            if ($mixValue instanceof \DateTime) {
                // Subclassing from a PHP DateTime object
                if ($objTimeZone) {
                    throw new Caller('QDateTime subclassing of a DateTime object cannot take in a DateTimeZone parameter');
                }
                parent::__construct($mixValue->format('Y-m-d H:i:s'), $mixValue->getTimezone());

                // By definition, a QDateTime object doesn't have anything nulled
                $this->blnDateNull = false;
                $this->blnTimeNull = false;
            } else {
                if (!$mixValue) {
                    // Set to "null date"
                    // And Do Nothing Else -- Default Values are already set to Nulled out
                    parent::__construct('2000-01-01 00:00:00', $objTimeZone);
                } else {
                    if (strtolower($mixValue) == QDateTime::NOW) {
                        // very common, so quickly deal with now string
                        parent::__construct('now', $objTimeZone);
                        $this->blnDateNull = false;
                        $this->blnTimeNull = false;
                    } else {
                        if (substr($mixValue, 0, 1) == '@') {
                            // unix timestamp. PHP superclass will always store ts in UTC. Our class will store in given timezone, or local tz
                            parent::__construct(date('Y-m-d H:i:s', substr($mixValue, 1)), $objTimeZone);
                            $this->blnDateNull = false;
                            $this->blnTimeNull = false;
                        } else {
                            // string relative date or time
                            if ($intTime = strtotime($mixValue)) {
                                // The documentation states that:
                                // The valid range of a timestamp is typically from
                                // Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 GMT.
                                // (These are the dates that correspond to the minimum and maximum values
                                // for a 32-bit signed integer).
                                //
                                // But experimentally, 0000-01-01 00:00:00 is the least date displayed correctly
                                if ($intTime < -62167241486) {
                                    // Set to "null date"
                                    // And Do Nothing Else -- Default Values are already set to Nulled out
                                    parent::__construct('2000-01-01 00:00:00', $objTimeZone);
                                } else {
                                    parent::__construct(date('Y-m-d H:i:s', $intTime), $objTimeZone);
                                    $this->blnDateNull = false;
                                    $this->blnTimeNull = false;
                                }
                            } else { // error
                                parent::__construct();
                                $this->blnDateNull = true;
                                $this->blnTimeNull = true;
                            }
                        }
                    }
                }
            }
        }

        // User is requesting to force a particular type.
        switch ($intType) {
            case QDateTime::DATE_ONLY_TYPE:
                $this->blnTimeNull = true;
                $this->reinforceNullProperties();
                return;
            case QDateTime::TIME_ONLY_TYPE:
                $this->blnDateNull = true;
                $this->reinforceNullProperties();
                return;
            case QDateTime::DATE_AND_TIME_TYPE:    // forcing both a date and time type to not be null
                $this->blnDateNull = false;
                $this->blnTimeNull = false;
                break;
            default:
                break;
        }
    }

    /**
     * Returns a new QDateTime object set to the last day of the specified month.
     *
     * @param int $intMonth
     * @param int $intYear
     * @return QDateTime the last day to a month in a year
     */
    public static function lastDayOfTheMonth($intMonth, $intYear)
    {
        $temp = date('Y-m-t', mktime(0, 0, 0, $intMonth, 1, $intYear));
        return new QDateTime($temp);
    }

    /**
     * Returns a new QDateTime object set to the first day of the specified month.
     *
     * @param int $intMonth
     * @param int $intYear
     * @return QDateTime the first day of the month
     */
    public static function firstDayOfTheMonth($intMonth, $intYear)
    {
        $temp = date('Y-m-d', mktime(0, 0, 0, $intMonth, 1, $intYear));
        return new QDateTime($temp);
    }

    /**
     * Formats a date as a string using the default format type.
     * @return string
     */
    public function __toString()
    {
        return $this->qFormat();
    }

    /**
     * The following code is a workaround for a PHP bug in 5.2 and greater (at least to 5.4).
     */
    //protected $strSerializedData;
    //protected $strSerializedTZ;
    public function serialize()
    {
        $tz = $this->getTimezone();
        if ($tz && in_array($tz->getName(), timezone_identifiers_list())) {
            $strTz = $tz->getName();
            $strDate = parent::format('Y-m-d H:i:s');
        } else {
            $strTz = null;
            $strDate = parent::format(QDateTime::ISO8601);
        }
        return serialize([
            1, // version number of serialized data, in case format changes
            $this->blnDateNull,
            $this->blnTimeNull,
            $strDate,
            $strTz
        ]);
    }

    public function unserialize($s)
    {
        $a = unserialize($s);
        $this->blnDateNull = $a[1];
        $this->blnTimeNull = $a[2];
        $tz = $a[4];
        if ($tz) {
            $tz = new \DateTimeZone($tz);
        } else {
            $tz = null;
        }
        parent::__construct($a[3], $tz);
    }

    /**
     * Outputs the date as a string given the format strFormat.  Will use
     * the static defaults if none given. This function is here for somewhat historical reasons, as it was originally
     * created before there was a built-in DateTime object.
     *
     * Properties of strFormat are (using Sunday, March 2, 1977 at 1:15:35 pm
     * in the following examples):
     *
     *    M - Month as an integer (e.g., 3)
     *    MM - Month as an integer with leading zero (e.g., 03)
     *    MMM - Month as three-letters (e.g., Mar)
     *    MMMM - Month as full name (e.g., March)
     *
     *    D - Day as an integer (e.g., 2)
     *    DD - Day as an integer with leading zero (e.g., 02)
     *    DDD - Day of week as three-letters (e.g., Wed)
     *    DDDD - Day of week as full name (e.g., Wednesday)
     *
     *    YY - Year as a two-digit integer (e.g., 77)
     *    YYYY - Year as a four-digit integer (e.g., 1977)
     *
     *    h - Hour as an integer in 12-hour format (e.g., 1)
     *    hh - Hour as an integer in 12-hour format with leading zero (e.g., 01)
     *    hhh - Hour as an integer in 24-hour format (e.g., 13)
     *    hhhh - Hour as an integer in 24-hour format with leading zero (e.g., 13)
     *
     *    mm - Minute as a two-digit integer
     *
     *    ss - Second as a two-digit integer
     *
     *    z - "pm" or "am"
     *    zz - "PM" or "AM"
     *    zzz - "p.m." or "a.m."
     *    zzzz - "P.M." or "A.M."
     *
     *  ttt - Timezone Abbreviation as a three-letter code (e.g. PDT, GMT)
     *  tttt - Timezone Identifier (e.g. America/Los_Angeles)
     *
     * @param string $strFormat the format of the date
     * @return string the formatted date as a string
     */
    public function qFormat($strFormat = null)
    {
        if ($this->blnDateNull && $this->blnTimeNull) {
            return '';
        }

        if (is_null($strFormat)) {
            if ($this->blnDateNull && !$this->blnTimeNull) {
                $strFormat = QDateTime::$DefaultTimeFormat;
            } elseif (!$this->blnDateNull && $this->blnTimeNull) {
                $strFormat = QDateTime::$DefaultDateOnlyFormat;
            } else {
                $strFormat = QDateTime::$DefaultFormat;
            }
        }

        preg_match_all('/(?(?=D)([D]+)|(?(?=M)([M]+)|(?(?=Y)([Y]+)|(?(?=h)([h]+)|(?(?=m)([m]+)|(?(?=s)([s]+)|(?(?=z)([z]+)|(?(?=t)([t]+)|))))))))/',
            $strFormat, $strArray);
        $strArray = $strArray[0];
        $strToReturn = '';

        $intStartPosition = 0;
        for ($intIndex = 0; $intIndex < count($strArray); $intIndex++) {
            $strToken = trim($strArray[$intIndex]);
            if ($strToken) {
                $intEndPosition = strpos($strFormat, $strArray[$intIndex], $intStartPosition);
                $strToReturn .= substr($strFormat, $intStartPosition, $intEndPosition - $intStartPosition);
                $intStartPosition = $intEndPosition + strlen($strArray[$intIndex]);

                switch ($strArray[$intIndex]) {
                    case 'M':
                        $strToReturn .= parent::format('n');
                        break;
                    case 'MM':
                        $strToReturn .= parent::format('m');
                        break;
                    case 'MMM':
                        $strToReturn .= parent::format('M');
                        break;
                    case 'MMMM':
                        $strToReturn .= parent::format('F');
                        break;

                    case 'D':
                        $strToReturn .= parent::format('j');
                        break;
                    case 'DD':
                        $strToReturn .= parent::format('d');
                        break;
                    case 'DDD':
                        $strToReturn .= parent::format('D');
                        break;
                    case 'DDDD':
                        $strToReturn .= parent::format('l');
                        break;

                    case 'YY':
                        $strToReturn .= parent::format('y');
                        break;
                    case 'YYYY':
                        $strToReturn .= parent::format('Y');
                        break;

                    case 'h':
                        $strToReturn .= parent::format('g');
                        break;
                    case 'hh':
                        $strToReturn .= parent::format('h');
                        break;
                    case 'hhh':
                        $strToReturn .= parent::format('G');
                        break;
                    case 'hhhh':
                        $strToReturn .= parent::format('H');
                        break;

                    case 'mm':
                        $strToReturn .= parent::format('i');
                        break;

                    case 'ss':
                        $strToReturn .= parent::format('s');
                        break;

                    case 'z':
                        $strToReturn .= parent::format('a');
                        break;
                    case 'zz':
                        $strToReturn .= parent::format('A');
                        break;
                    case 'zzz':
                        $strToReturn .= sprintf('%s.m.', substr(parent::format('a'), 0, 1));
                        break;
                    case 'zzzz':
                        $strToReturn .= sprintf('%s.M.', substr(parent::format('A'), 0, 1));
                        break;

                    case 'ttt':
                        $strToReturn .= parent::format('T');
                        break;
                    case 'tttt':
                        $strToReturn .= parent::format('e');
                        break;
                    case 'ttttt':
                        $strToReturn .= parent::format('O');
                        break;

                    default:
                        $strToReturn .= $strArray[$intIndex];
                }
            }
        }

        if ($intStartPosition < strlen($strFormat)) {
            $strToReturn .= substr($strFormat, $intStartPosition);
        }

        return $strToReturn;
    }

    /**
     * Sets the time portion to the given time. If a QDateTime is given, will use the time portion of that object.
     * Works around a problem in php that if you set the time across a daylight savings time boundary, the timezone
     * does not advance. This version will detect that and advance the timezone.
     *
     * @param int|QDateTime $mixValue
     * @param int|null $intMinute
     * @param int|null $intSecond
     * @param int|null $intMicroSeconds
     * @return $this
     */
    public function setTime($mixValue, $intMinute = null, $intSecond = null, $intMicroSeconds = null): \DateTime
    {
        // Check if $mixValue is a QDateTime object
        if ($mixValue instanceof QDateTime) {
            if ($mixValue->isTimeNull()) {
                $this->blnTimeNull = true;
                $this->reinforceNullProperties();
                return $this;
            }
            // Normalize time zones
            $tz = $this->getTimezone();
            if ($tz && in_array($tz->getName(), timezone_identifiers_list())) {
                $mixValue->setTimezone($tz);
            }
            $intHour = $mixValue->Hour;
            $intMinute = $mixValue->Minute;
            $intSecond = $mixValue->Second;
        } else {
            $intHour = $mixValue;
        }

        // If HOUR or MINUTE is NULL
        if (is_null($intHour) || is_null($intMinute)) {
            $intMicroSeconds = $intMicroSeconds ?? 0;
            if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
                parent::setTime($intHour, $intMinute, $intSecond, $intMicroSeconds);
            } else {
                parent::setTime($intHour, $intMinute, $intSecond);
            }
            $this->blnTimeNull = true;
            $this->reinforceNullProperties();
            return $this;
        }

        // Convert and set values
        $intHour = Type::cast($intHour, Type::INTEGER);
        $intMinute = Type::cast($intMinute, Type::INTEGER);
        $intSecond = Type::cast($intSecond, Type::INTEGER);
        $intMicroSeconds = $intMicroSeconds ?? 0;
        $this->blnTimeNull = false;

        // Setting the time
        if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
            parent::setTime($intHour, $intMinute, $intSecond, $intMicroSeconds);
        } else {
            parent::setTime($intHour, $intMinute, $intSecond);
        }

        return $this;
    }

    /**
     * Set the date.
     *
     * @param int $intYear
     * @param int $intMonth
     * @param int $intDay
     * @return $this|QDateTime
     */
    public function setDate(int $intYear, int $intMonth, int $intDay): \DateTime
    {
        // Turvaliised tüüpi muutmised
        $intYear = Type::cast($intYear, Type::INTEGER);
        $intMonth = Type::cast($intMonth, Type::INTEGER);
        $intDay = Type::cast($intDay, Type::INTEGER);
        $this->blnDateNull = false;

        // Tell the parent to execute its version of this method
        parent::setDate($intYear, $intMonth, $intDay);

        // Return the current object to satisfy method chaining
        return $this;
    }

    protected function reinforceNullProperties()
    {
        if ($this->blnDateNull) {
            parent::setDate(2000, 1, 1);
        }
        if ($this->blnTimeNull) {
            parent::setTime(0, 0, 0);
        }
    }

    /**
     * Converts the current QDateTime object to a different TimeZone.
     *
     * TimeZone should be passed in as a string-based identifier.
     *
     * Note that this is different than the built-in QDateTime::setTimezone() method which expicitly
     * takes in a DateTimeZone object.  QDateTime::ConvertToTimezone allows you to specify any
     * string-based Timezone identifier.  If none is specified and/or if the specified timezone
     * is not a valid identifier, it will simply remain unchanged as opposed to throwing an exeception
     * or error.
     *
     * @param string $strTimezoneIdentifier a string-based parameter specifying a timezone identifier (e.g. America/Los_Angeles)
     * @return void
     */
    public function convertToTimezone($strTimezoneIdentifier)
    {
        try {
            $dtzNewTimezone = new \DateTimeZone($strTimezoneIdentifier);
            $this->setTimezone($dtzNewTimezone);
        } catch (\Exception $objExc) {
        }
    }

    /**
     * Returns true if give QDateTime is the same.
     *
     * @param QDateTime $dttCompare
     * @return bool
     */
    public function isEqualTo(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;

            // Return the Result
            return ($dttThis->Timestamp == $dttThat->Timestamp);
        } else {
            // Return the Result for the both Date and Time components
            return ($this->Timestamp == $dttCompare->Timestamp);
        }
    }

    /**
     * Returns true if current date time is earlier than the given one.
     * @param QDateTime $dttCompare
     * @return bool
     */
    public function isEarlierThan(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;

            // Return the Result
            return ($dttThis->Timestamp < $dttThat->Timestamp);
        } else {
            // Return the Result for the both Date and Time components
            return ($this->Timestamp < $dttCompare->Timestamp);
        }
    }

    /**
     * Returns true if current date time is earlier than the given one.
     * @param QDateTime $dttCompare
     * @return bool
     */
    public function isEarlierOrEqualTo(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;

            // Return the Result
            return ($dttThis->Timestamp <= $dttThat->Timestamp);
        } else {
            // Return the Result for the both Date and Time components
            return ($this->Timestamp <= $dttCompare->Timestamp);
        }
    }

    /**
     * Returns true if current date time is later than the given one.
     * @param QDateTime $dttCompare
     * @return bool
     */
    public function isLaterThan(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;

            // Return the Result
            return ($dttThis->Timestamp > $dttThat->Timestamp);
        } else {
            // Return the Result for the both Date and Time components
            return ($this->Timestamp > $dttCompare->Timestamp);
        }
    }

    /**
     * Returns true if current date time is later than or equal to the given one.
     * @param QDateTime $dttCompare
     * @return bool
     */
    public function isLaterOrEqualTo(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;

            // Return the Result
            return ($dttThis->Timestamp >= $dttThat->Timestamp);
        } else {
            // Return the Result for the both Date and Time components
            return ($this->Timestamp >= $dttCompare->Timestamp);
        }
    }

    /**
     * Compare the current date with the given date. Return -1 if current date is less than given date, 0 if equal,
     * and 1 if greater. Null dates are considered the earliest possible date.
     *
     * @param QDateTime $dttCompare
     * @return int
     */
    public function compare(QDateTime $dttCompare)
    {
        // All comparison operations MUST have operands with matching Date Nullstates
        if ($this->blnDateNull && !$dttCompare->blnDateNull) {
            return -1;
        } elseif (!$this->blnDateNull && $dttCompare->blnDateNull) {
            return 1;
        }

        // If mismatched Time nullstates, then only compare the Date portions
        if ($this->blnTimeNull != $dttCompare->blnTimeNull) {
            // Let's "Null Out" the Time
            $dttThis = new QDateTime($this);
            $dttThat = new QDateTime($dttCompare);
            $dttThis->Hour = null;
            $dttThat->Hour = null;
        } else {
            $dttThis = $this;
            $dttThat = $dttCompare;
        }
        return ($dttThis->Timestamp < $dttThat->Timestamp ? -1 : ($dttThis->Timestamp == $dttThat->Timestamp ? 0 : 1));
    }

    /**
     * Returns the difference as a QDateSpan, which is easier to work with and more full featured than
     * the php DateTimeInterval class.
     *
     * @param QDateTime $dttDateTime
     * @return DateTimeSpan
     */
    public function difference(QDateTime $dttDateTime)
    {
        $intDifference = $this->Timestamp - $dttDateTime->Timestamp;
        return new DateTimeSpan($intDifference);
    }

    /**
     * Add a datespan to the current date. Use add for adding a date interval.
     *
     * @param \DateInterval|DateTimeSpan $dtsSpan
     * @return $this
     */
    public function addSpan(DateTimeSpan $dtsSpan)
    {
        // And add the Span Second count to it
        $this->Timestamp = $this->Timestamp + $dtsSpan->Seconds;
        return $this;
    }

    /**
     * Add a number of seconds. Use negative value to go earlier in time.
     *
     * @param integer $intSeconds
     * @return QDateTime
     */
    public function addSeconds($intSeconds)
    {
        $this->Second += $intSeconds;
        return $this;
    }

    /**
     * Add minutes to the time.
     *
     * @param integer $intMinutes
     * @return QDateTime
     */
    public function addMinutes($intMinutes)
    {
        $this->Minute += $intMinutes;
        return $this;
    }

    /**
     * Add hours to the time.
     *
     * @param integer $intHours
     * @return QDateTime
     */
    public function addHours($intHours)
    {
        $this->Hour += $intHours;
        return $this;
    }

    /**
     * Add days to the time.
     *
     * @param integer $intDays
     * @return QDateTime
     */
    public function addDays($intDays)
    {
        $this->Day += $intDays;
        return $this;
    }

    /**
     * Add months to the time. If the day on the new month is greater than the month will allow, the day is adjusted
     * to be the last day of that month.
     *
     * @param integer $intMonths
     * @return QDateTime
     */
    public function addMonths($intMonths)
    {
        $prevDay = $this->Day;
        $this->Month += $intMonths;
        if ($this->Day != $prevDay) {
            $this->Day = 1;
            $this->addDays(-1);
        }
        return $this;
    }

    /**
     * Add years to the time.
     *
     * @param integer $intYears
     * @return QDateTime
     */
    public function addYears($intYears)
    {
        $this->Year += $intYears;
        return $this;
    }

    /**
     * Modifies the date or time based on values found int a string.
     *
     * @see QDateTime::modify()
     * @param string $mixValue
     * @return QDateTime
     */
    public function modify(string $mixValue): \DateTime
    {
        parent::modify($mixValue);
        return $this;
    }

    /**
     * Convert the object to a javascript object. This is code that if executed in javascript would produce a Date
     * javascript object. Note that the date will be created in the browser's local timezone, so convert to the
     * browser's timezone first if that is important for you.
     *
     * @return string
     */
    public function toJsObject()
    {
        if ($this->blnDateNull) {
            $dt = self::now();    // time only will use today's date.
            $dt->setTime($this);
        } else {
            $dt = clone $this;
        }

        if ($this->blnTimeNull) {
            return sprintf('new Date(%s, %s, %s)', $dt->Year, $dt->Month - 1, $dt->Day);
        } else {
            return sprintf('new Date(%s, %s, %s, %s, %s, %s)', $dt->Year, $dt->Month - 1, $dt->Day, $dt->Hour,
                $dt->Minute, $dt->Second);
        }
    }

    /**
     * Returns a datetime in a way that it will pass through a json_encode and be decodable in qcubed.js.
     * qcubed.unpackParams looks for this.
     *
     * @return array|mixed;
     */
    public function jsonSerialize(): mixed
    {
        if ($this->blnDateNull) {
            $dt = self::now();    // time only will use today's date.
            $dt->setTime($this);
        } else {
            $dt = clone $this;
        }

        if ($this->blnTimeNull) {
            return [
                QCubed::JSON_OBJECT_TYPE => 'qDateTime',
                'year' => $dt->Year,
                'month' => $dt->Month - 1,
                'day' => $dt->Day
            ];
        } else {
            return [
                QCubed::JSON_OBJECT_TYPE => 'qDateTime',
                'year' => $dt->Year,
                'month' => $dt->Month - 1,
                'day' => $dt->Day,
                'hour' => $dt->Hour,
                'minute' => $dt->Minute,
                'second' => $dt->Second
            ];
        }
    }

    /**
     * PHP magic method
     * @param $strName
     *
     * @return QDateTime|string
     * @throws UndefinedProperty
     */
    public function __get($strName)
    {
        switch ($strName) {
            case 'Month':
                if ($this->blnDateNull) {
                    return null;
                } else {
                    return (int)parent::format('m');
                }

            case 'Day':
                if ($this->blnDateNull) {
                    return null;
                } else {
                    return (int)parent::format('d');
                }

            case 'Year':
                if ($this->blnDateNull) {
                    return null;
                } else {
                    return (int)parent::format('Y');
                }

            case 'Hour':
                if ($this->blnTimeNull) {
                    return null;
                } else {
                    return (int)parent::format('H');
                }

            case 'Minute':
                if ($this->blnTimeNull) {
                    return null;
                } else {
                    return (int)parent::format('i');
                }

            case 'Second':
                if ($this->blnTimeNull) {
                    return null;
                } else {
                    return (int)parent::format('s');
                }

            case 'Timestamp':
                return (int)parent::format('U'); // range depends on the platform's max and min integer values

            case 'Age':
                // Figure out the Difference from "Now"
                $dtsFromCurrent = $this->difference(self::now());

                // It's in the future ('about 2 hours from now')
                if ($dtsFromCurrent->isPositive()) {
                    $strTime = $dtsFromCurrent->simpleDisplay();
                    return sprintf(t('%s from now'), $strTime);
                } // It's in the past ('about 5 hours ago')
                else {
                    if ($dtsFromCurrent->isNegative()) {
                        $dtsFromCurrent->Seconds = abs($dtsFromCurrent->Seconds);
                        $strTime = $dtsFromCurrent->simpleDisplay();
                        return sprintf(t('%s ago'), $strTime);

                        // It's current
                    } else {
                        return t('right now');
                    }
                }

            case 'LastDayOfTheMonth':
                return self::lastDayOfTheMonth($this->Month, $this->Year);
            case 'FirstDayOfTheMonth':
                return self::firstDayOfTheMonth($this->Month, $this->Year);
            default:
                throw new UndefinedProperty('GET', 'DateTime', $strName);
        }
    }

    /**
     * PHP magic method
     *
     * @param $strName
     * @param $mixValue
     *
     * @return mixed
     * @throws \Exception|Caller|Caller|InvalidCast|UndefinedProperty
     */
    public function __set($strName, $mixValue)
    {
        try {
            switch ($strName) {
                case 'Month':
                    if ($this->blnDateNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Month property on a null date.  Use SetDate().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnDateNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate(parent::format('Y'), $mixValue, parent::format('d'));
                    return $mixValue;

                case 'Day':
                    if ($this->blnDateNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Day property on a null date.  Use SetDate().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnDateNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate(parent::format('Y'), parent::format('m'), $mixValue);
                    return $mixValue;

                case 'Year':
                    if ($this->blnDateNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Year property on a null date.  Use SetDate().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnDateNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate($mixValue, parent::format('m'), parent::format('d'));
                    return $mixValue;

                case 'Hour':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Hour property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime($mixValue, parent::format('i'), parent::format('s'));
                    return $mixValue;

                case 'Minute':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Minute property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime(parent::format('H'), $mixValue, parent::format('s'));
                    return $mixValue;

                case 'Second':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Second property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return null;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime(parent::format('H'), parent::format('i'), $mixValue);
                    return $mixValue;

                case 'Timestamp':
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    $this->setTimestamp($mixValue);
                    $this->blnDateNull = false;
                    $this->blnTimeNull = false;
                    return $mixValue;

                default:
                    throw new UndefinedProperty('SET', 'DateTime', $strName);
            }
        } catch (InvalidCast $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }
}

\DateTime::RFC1123;