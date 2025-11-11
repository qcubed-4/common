<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

// These Aids with the PHP 5.2 QDateTime error handling
use DateTimeInterface;
use QCubed\Exception\Caller;
use QCubed\Exception\InvalidCast;
use DateMalformedStringException;
use DateInvalidTimeZoneException;
use QCubed\Exception\UndefinedProperty;
use Exception;
use JsonSerializable;
use Serializable;
use DateTimeZone;
use DateTime;

require_once (dirname(__DIR__) . '/i18n/i18n-lib.inc.php');

/**
 * QDateTime
 * This QDateTime class provides a nice wrapper around the PHP QDateTime class,
 * which is included with all versions of PHP >= 5.2.0. It includes many enhancements,
 * including the ability to specify a null date or time portion to represent a date-only or time- * only object.
 *
 * Inherits from the PHP DateTime object, and the built-in methods are available for you to call
 * as well. In particular, note that the built-in format and the qFormat routines here take different
 * specifiers. Feel free to use too.
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
 * @property-read string $Age String representation of age compared to the current age.
 * @property-read QDateTime $LastDayOfTheMonth  A new QDateTime representing the last day of this date's month.
 * @property-read QDateTime $FirstDayOfTheMonth A new QDateTime representing the first day of this date's month.
 * @method gFormat(string $string)
 */
class QDateTime extends DateTime implements JsonSerializable, Serializable
{
    /** Used to specify the time right now (used when creating new instances of this class) */
    const string NOW = 'now';

    /** These formatters are for the qFormat function */
    const string FORMAT_ISO = 'YYYY-MM-DD hhhh:mm:ss'; // Date and time in ISO format
    const string FORMAT_ISO_COMPRESSED = 'YYYYMMDDhhhhmmss'; //Date and time in ISO compressed format
    const string FORMAT_DISPLAY_DATE = 'MMM DD YYYY'; //Format used for displaying short date
    const string FORMAT_DISPLAY_DATE_FULL = 'DDD, MMMM D, YYYY'; //Format used for displaying the full date
    const string FORMAT_DISPLAY_DATE_TIME = 'MMM DD YYYY hh:mm zz'; //Format used for displaying the short date and time
    const string FORMAT_DISPLAY_DATE_TIME_FULL = 'DDDD, MMMM D, YYYY, h:mm:ss zz'; //Format used for displaying the full date and time
    const string FORMAT_DISPLAY_TIME = 'hh:mm:ss zz'; //Format to display only the time
    const string FORMAT_RFC_822 = 'DDD, DD MMM YYYY hhhh:mm:ss ttt'; //Date and time format according to RFC 822
    const string FORMAT_RFC_5322 = 'DDD, DD MMM YYYY hhhh:mm:ss ttttt'; //Date and time format according to RFC 5322
    const string FORMAT_SOAP = 'YYYY-MM-DDThhhh:mm:ss'; //Format used to represent date for SOAP

    /** Note that you can also call the inherited format() function with the following built-in constants */

    const string ATOM = 'Y-m-d\TH:i:sP';
    const string COOKIE = 'l, d-M-y H:i:s T';
    const string ISO8601 = 'Y-m-d\TH:i:sO';
    const string RFC822 = 'D, d M y H:i:s O';
    const string RFC850 = 'l, d-M-y H:i:s T';
    const string RFC1036 = 'D, d M y H:i:s O';
    const string RFC1123 = 'D, d M Y H:i:s O';
    const string RFC2822 = 'D, d M Y H:i:s O';
    const string RFC3339 = 'Y-m-d\TH:i:sP';
    const string RSS = 'D, d M Y H:i:s O';
    const string W3C = 'Y-m-d\TH:i:sP';


    /* Type in which the date and time have to be interpreted */
    const int UNKNOWN_TYPE = 0;
    const int DATE_ONLY_TYPE = 1;
    const int TIME_ONLY_TYPE = 2;
    const int DATE_AND_TIME_TYPE = 3;
    /**
     * @var null|int|\QCubed\QDateTime|string
     */
    public string|int|null|QDateTime $Format;

    /** @var bool true if the date is null */
    protected bool $blnDateNull = true;
    /** @var bool  true if time is null, rather than just zero (beginning of day) */
    protected bool $blnTimeNull = true;


    /**
     * The "Default" Display Format
     * @var string $DefaultFormat
     */
    public static string $DefaultFormat = QDateTime::FORMAT_DISPLAY_DATE_TIME;

    /**
     * The "Default" Display Format for Times
     * @var string $DefaultTimeFormat
     */
    public static string $DefaultTimeFormat = QDateTime::FORMAT_DISPLAY_TIME;

    /**
     * The "Default" Display Format for Dates with null times
     * @var string $DefaultDateOnlyFormat
     */
    public static string $DefaultDateOnlyFormat = QDateTime::FORMAT_DISPLAY_DATE;

    /**
     * Creates a new QDateTime object representing the current date and time.
     *
     * This method generates a QDateTime instance set to the current system time.
     * Optionally, it can create the object with a null time value if specified.
     *
     * @param bool $blnTimeValue Indicates whether to include the current time. Defaults to true. If false, the time is set to null.
     *
     * @return QDateTime A QDateTime object representing the current date, with or without time, based on the parameter.
     */
    public static function now(bool $blnTimeValue = true): QDateTime
    {
        $dttToReturn = new QDateTime(QDateTime::NOW);
        if (!$blnTimeValue) {
            $dttToReturn->blnTimeNull = true;
            $dttToReturn->reinforceNullProperties();
        }
        return $dttToReturn;
    }

    /**
     * Returns the current date and time as a formatted string.
     *
     * This method retrieves the current date and time, optionally formats it
     * based on the provided format string, and returns the result.
     *
     * @param string|null $strFormat An optional format string for the date and time.
     *                                If null, the default format will be used.
     *
     * @return string The formatted string representation of the current date and time.
     */
    public static function nowToString(?string $strFormat = null): string
    {
        $dttNow = new QDateTime(QDateTime::NOW);
        return $dttNow->qFormat($strFormat);
    }

    /**
     * Checks whether the date property of the object is null.
     *
     * This function determines if the date component of the object has not been set
     * or is explicitly marked as null.
     *
     * @return bool True if the date is null; otherwise, false.
     */
    public function isDateNull(): bool
    {
        return $this->blnDateNull;
    }

    /**
     * Determines if both the date and time properties of the object are null.
     *
     * This method checks the internal date and time null flags to evaluate
     * whether the object's date and time values are unset.
     *
     * @return bool True if both date and time are null, otherwise false.
     */
    public function isNull(): bool
    {
        return ($this->blnDateNull && $this->blnTimeNull);
    }

    /**
     * Determines if the time component of the object is null.
     *
     * This method checks whether the time value of the object is considered null
     * based on the internal `blnTimeNull` property.
     *
     * @return bool True if the time component is null; false otherwise.
     */
    public function isTimeNull(): bool
    {
        return $this->blnTimeNull;
    }

    /**
     * Formats the date according to the given format string.
     *
     * This method utilizes the parent's format method to return
     * the date in the specified format.
     *
     * @param string $strFormat The format to use for the date. Must follow valid date formatting rules.
     *
     * @return string The formatted date as a string.
     */
    public function phpDate(string $strFormat): string
    {
        // This just makes a call to format
        return parent::format($strFormat);
    }

    /**
     * Converts an array of QDateTime objects into an array of SOAP datetime strings.
     *
     * This method iterates through an array of QDateTime objects and formats
     * each object using the SOAP datetime format. If the input array is null
     * or empty, the method returns null.
     *
     * @param array|null $dttArray An array of QDateTime objects to be converted, or null if no input is provided.
     *
     * @return array|null An array of strings in THE SOAP datetime format, or null if the input array is null or empty.
     */
    public function getSoapDateTimeArray(?array $dttArray): ?array
    {
        if (!$dttArray) {
            return null;
        }

        $strArrayToReturn = array();
        foreach ($dttArray as $dttItem) {
            $strArrayToReturn[] = $dttItem->qFormat(QDateTime::FORMAT_SOAP);
        }
        return $strArrayToReturn;
    }

    /**
     * Creates a QDateTime object from a given Unix timestamp and an optional time zone.
     *
     * This method generates a QDateTime instance using the provided timestamp, formatted
     * as a date and time string. If a time zone is specified, it will be applied to the
     * resulting QDateTime object.
     *
     * @param int $intTimestamp The Unix timestamp to be converted into a QDateTime object.
     * @param DateTimeZone|null $objTimeZone An optional time zone to apply to the QDateTime object.
     *
     * @return QDateTime The QDateTime object created from the given timestamp and time zone.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public static function fromTimestamp(int $intTimestamp, ?DateTimeZone $objTimeZone = null): QDateTime
    {

        return new QDateTime(date('Y-m-d H:i:s', $intTimestamp), $objTimeZone);
    }

    /**
     * Constructs a new QDateTime object. Provides various initialization options such as cloning or subclassing
     * from other QDateTime or DateTime objects, setting a specific timestamp, or utilizing string-based date/time values.
     * Handles null settings and date/time type enforcement.
     *
     * @param mixed|null $mixValue Initialization value, which can be a QDateTime object, DateTime object, a string
     *                              (e.g., 'now', timestamp), or null for the "null date".
     * @param DateTimeZone|null $objTimeZone An optional timezone to set for the DateTime object. Certain operations do not
     *                                        allow this parameter and will throw an exception if provided.
     * @param int $intType Specifies the type of date/time (e.g., DATE_ONLY_TYPE, TIME_ONLY_TYPE, DATE_AND_TIME_TYPE).
     *                     Default to QDateTime::UNKNOWN_TYPE.
     *
     * @return void
     * @throws DateMalformedStringException
     * @throws Caller If invalid, combinations of parameters are provided, such as cloning while providing a timezone.
     */
    public function __construct(mixed $mixValue = null, ?DateTimeZone $objTimeZone = null, int $intType = QDateTime::UNKNOWN_TYPE)
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
            if ($mixValue instanceof DateTime) {
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
                        if (str_starts_with($mixValue, '@')) {
                            // Unix timestamp. PHP superclass will always store ts in UTC. Our class will be stored in a given timezone, or local tz
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
     *
     * @return QDateTime the last day to a month in a year
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public static function lastDayOfTheMonth(int $intMonth, int $intYear): QDateTime
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
     * @throws Caller
     * @throws DateMalformedStringException
     */
    public static function firstDayOfTheMonth(int $intMonth, int $intYear): QDateTime
    {
        $temp = date('Y-m-d', mktime(0, 0, 0, $intMonth, 1, $intYear));
        return new QDateTime($temp);
    }

    /**
     * Formats a date as a string using the default format type.
     * @return string
     */
    public function __toString(): string
    {
        return $this->qFormat();
    }

    /**
     * Serializes the current object into a storable string representation.
     *
     * This function evaluates the object's date, time, and timezone properties
     * and converts them into a format suitable for serialization. Additional metadata
     * such as a version number is included for future compatibility.
     *
     * @return string A serialized string representation of the date, time, and timezone properties of the object.
     */
    public function serialize(): string
    {
        $tz = $this->getTimezone();
        if ($tz && in_array($tz->getName(), timezone_identifiers_list())) {
            $strTz = $tz->getName();
            $strDate = parent::format('Y-m-d H:i:s');
        } else {
            $strTz = null;
            $strDate = parent::format(DateTimeInterface::ATOM);
        }
        return serialize([
            1, // version number of serialized data, in case the format changes
            $this->blnDateNull,
            $this->blnTimeNull,
            $strDate,
            $strTz
        ]);
    }

    /**
     * Restores the object's state from a serialized string.
     *
     * This method takes a serialized string, unserializes it, and reconstructs
     * the state of the object based on the extracted data.
     *
     * @param string $s The serialized string containing the object's state.
     *
     * @return void
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     */
    public function unserialize(string $s): void
    {
        $a = unserialize($s);
        $this->blnDateNull = $a[1];
        $this->blnTimeNull = $a[2];
        $tz = $a[4];
        if ($tz) {
            $tz = new DateTimeZone($tz);
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
     * Properties of strFormat are (using Wednesday, March 2, 1977 at 1:15:35 pm
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
     * h – hour as an integer in the 12-hour format (e.g., 1)
     *    hh - Hour as an integer in the 12-hour format with leading zero (e.g., 01)
     *    hhh - Hour as an integer in the 24-hour format (e.g., 13)
     *    hhhh - Hour as an integer in the 24-hour format with leading zero (e.g., 13)
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
     *  ttt - Timezone Abbreviation as a three-letter code (e.g., PDT, GMT)
     *  tttt - Timezone Identifier (e.g., America/Los_Angeles)
     *
     * @param string|null $strFormat the format of the date
     * @return string the formatted date as a string
     */
    public function qFormat(?string $strFormat = null): string
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

                $strToReturn .= match ($strArray[$intIndex]) {
                    'M' => parent::format('n'),
                    'MM' => parent::format('m'),
                    'MMM' => parent::format('M'),
                    'MMMM' => parent::format('F'),
                    'D' => parent::format('j'),
                    'DD' => parent::format('d'),
                    'DDD' => parent::format('D'),
                    'DDDD' => parent::format('l'),
                    'YY' => parent::format('y'),
                    'YYYY' => parent::format('Y'),
                    'h' => parent::format('g'),
                    'hh' => parent::format('h'),
                    'hhh' => parent::format('G'),
                    'hhhh' => parent::format('H'),
                    'mm' => parent::format('i'),
                    'ss' => parent::format('s'),
                    'z' => parent::format('a'),
                    'zz' => parent::format('A'),
                    'zzz' => sprintf('%s.m.', substr(parent::format('a'), 0, 1)),
                    'zzzz' => sprintf('%s.M.', substr(parent::format('A'), 0, 1)),
                    'ttt' => parent::format('T'),
                    'tttt' => parent::format('e'),
                    'ttttt' => parent::format('O'),
                    default => $strArray[$intIndex],
                };
            }
        }

        if ($intStartPosition < strlen($strFormat)) {
            $strToReturn .= substr($strFormat, $intStartPosition);
        }

        return $strToReturn;
    }

    /**
     * Sets the time for the current object. Accepts an instance of QDateTime or individual hour, minute, second, and microseconds parameters.
     * Automatically handles timezone adjustments if the input is a QDateTime object. Handles null values for an hour or minute gracefully.
     *
     * @param mixed $mixValue The hour as an integer or a QDateTime object from which time will be extracted.
     * @param int|null $intMinute The minute value (optional).
     * @param int|null $intSecond The second value (optional).
     * @param int|null $intMicroSeconds The microsecond value (optional, defaults to 0).
     *
     * @return DateTime The current object with updated time.
     * @throws Caller
     * @throws InvalidCast
     */
    public function setTime(mixed $mixValue, ?int $intMinute = null, ?int $intSecond = null, ?int $intMicroSeconds = null): DateTime
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
     * Sets the date properties of the current object using the provided year, month, and day values.
     *
     * This method overrides the parent implementation of `setDate` and ensures the internal date state
     * is updated correctly. It also allows for method chaining by returning the current object instance.
     *
     * @param int $intYear The year value to set.
     * @param int $intMonth The month value to set.
     * @param int $intDay The day value to set.
     *
     * @return DateTime The current object instance for method chaining.
     * @throws Caller
     * @throws InvalidCast
     */
    public function setDate(int $intYear, int $intMonth, int $intDay): DateTime
    {
        // Security type changes
        $intYear = Type::cast($intYear, Type::INTEGER);
        $intMonth = Type::cast($intMonth, Type::INTEGER);
        $intDay = Type::cast($intDay, Type::INTEGER);
        $this->blnDateNull = false;

        // Tell the parent to execute its version of this method
        parent::setDate($intYear, $intMonth, $intDay);

        // Return the current object to satisfy method chaining
        return $this;
    }

    /**
     * Reinforces null values for date and time properties by setting default values.
     *
     * This method checks the object's date and time properties for null values.
     * If a date property is null, it sets a default date of January 1, 2000.
     * If a time property is null, it sets a default time of 00:00:00.
     *
     * @return void
     */
    protected function reinforceNullProperties(): void
    {
        if ($this->blnDateNull) {
            parent::setDate(2000, 1, 1);
        }
        if ($this->blnTimeNull) {
            parent::setTime(0, 0);
        }
    }

    /**
     * Converts the current QDateTime object to a different TimeZone.
     *
     * TimeZone should be passed in as a string-based identifier.
     *
     * Note that this is different than the built-in QDateTime::setTimezone() method which exactly
     * takes in a DateTimeZone object.  QDateTime::ConvertToTimezone allows you to specify any
     * string-based Timezone identifier.  If none is specified and/or if the specified timezone
     * is not a valid identifier, it will simply remain unchanged as opposed to throwing an exception
     * or error.
     *
     * @param string $strTimezoneIdentifier a string-based parameter specifying a timezone identifier (e.g., America/Los_Angeles)
     * @return void
     */
    public function convertToTimezone(string $strTimezoneIdentifier): void
    {
        try {
            $dtzNewTimezone = new DateTimeZone($strTimezoneIdentifier);
            $this->setTimezone($dtzNewTimezone);
        } catch (Exception $objExc) {
        }
    }

    /**
     * Determines if the current QDateTime object is equal to the provided QDateTime object.
     * The equality check takes into account the date and, if applicable, the time components,
     * considering the null status of date and time for both objects.
     *
     * @param QDateTime $dttCompare The QDateTime object to compare against.
     * @return bool True if the two QDateTime objects are considered equal; otherwise, false.
     * @throws Caller
     * @throws DateMalformedStringException
     */
    public function isEqualTo(QDateTime $dttCompare): bool
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Determines whether the current QDateTime object is earlier than the specified QDateTime object.
     *
     * @param QDateTime $dttCompare The QDateTime object to compare against.
     *
     * @return bool True if the current QDateTime object is earlier than the provided QDateTime object, otherwise false.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public function isEarlierThan(QDateTime $dttCompare): bool
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Determines if the current QDateTime object is earlier than or equal to the provided QDateTime object.
     * The comparison accounts for matching Date and Time null states. If time null states are mismatched,
     * only the date portions are compared.
     *
     * @param QDateTime $dttCompare The date and time object to compare against.
     *
     * @return bool Returns true if the current object is earlier or equal to the provided object, false otherwise.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public function isEarlierOrEqualTo(QDateTime $dttCompare): bool
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Determines if the current date-time object is later than the given date-time object.
     * The comparison respects the null state of both date and time components to ensure consistent logic.
     *
     * @param QDateTime $dttCompare The date-time object to compare against.
     *
     * @return bool True if the current date-time object is later than the provided date-time object, false otherwise.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public function isLaterThan(QDateTime $dttCompare): bool
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Determines whether the current QDateTime object is later than or equal to the specified QDateTime object.
     * This comparison considers both the date and time components if applicable, or only the date component when
     * time null states differ.
     *
     * @param QDateTime $dttCompare The QDateTime object to compare against.
     *
     * @return bool True if the current QDateTime object is later than or equal to the specified QDateTime object,
     *              otherwise false.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public function isLaterOrEqualTo(QDateTime $dttCompare): bool
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull != $dttCompare->blnDateNull) {
            return false;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Compares the current QDateTime object to another QDateTime object.
     * The comparison takes into account the date and time null states of both objects.
     *
     * @param QDateTime $dttCompare The QDateTime object to compare against.
     *
     * @return int Returns -1 if the current object is less than the compared object,
     *             0 if they are equal, and 1 if the current object is greater.
     * @throws DateMalformedStringException
     * @throws Caller
     */
    public function compare(QDateTime $dttCompare): int
    {
        // All comparison operations MUST have operands with matching Date Null states
        if ($this->blnDateNull && !$dttCompare->blnDateNull) {
            return -1;
        } elseif (!$this->blnDateNull && $dttCompare->blnDateNull) {
            return 1;
        }

        // If mismatched Time null states, then only compare the Date portions
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
     * Calculate the difference between the current datetime object and another provided datetime object.
     * The difference is returned as a DateTimeSpan object representing the time span between the two dates.
     *
     * @param QDateTime $dttDateTime The datetime object to compare with the current datetime.
     * @return DateTimeSpan The calculated time span as a DateTimeSpan object.
     */
    public function difference(QDateTime $dttDateTime): DateTimeSpan
    {
        $intDifference = $this->Timestamp - $dttDateTime->Timestamp;
        return new DateTimeSpan($intDifference);
    }

    /**
     * Adds a DateTimeSpan to the current object by incrementing the timestamp with the seconds from the provided span.
     *
     * @param DateTimeSpan $dtsSpan The time span to add, represented in seconds.
     * @return $this
     */
    public function addSpan(DateTimeSpan $dtsSpan): static
    {
        // And add the Span Second count to it
        $this->Timestamp = $this->Timestamp + $dtsSpan->Seconds;
        return $this;
    }

    /**
     * Adds the specified number of seconds to the current time object.
     *
     * @param int $intSeconds The number of seconds to add.
     * @return $this
     */
    public function addSeconds(int $intSeconds): static
    {
        $this->Second += $intSeconds;
        return $this;
    }

    /**
     * Adds the specified number of minutes to the current time object.
     *
     * @param int $intMinutes The number of minutes to add. Can be a positive or negative integer.
     * @return $this The updated time object with the added minutes.
     */
    public function addMinutes(int $intMinutes): static
    {
        $this->Minute += $intMinutes;
        return $this;
    }

    /**
     * Adds the specified number of hours to the current object.
     *
     * @param int $intHours The number of hours to add.
     * @return $this The current object with updated hours.
     */
    public function addHours(int $intHours): static
    {
        $this->Hour += $intHours;
        return $this;
    }

    /**
     * Adds a specified number of days to the current date object.
     *
     * @param int $intDays The number of days to add.
     * @return $this The current instance with the updated date.
     */
    public function addDays(int $intDays): static
    {
        $this->Day += $intDays;
        return $this;
    }

    /**
     * Adjust the current date by adding the specified number of months.
     * If the target month does not have the original day (e.g., adding a month to January 31),
     * the date will default to the last day of the resulting month.
     *
     * @param int $intMonths The number of months to add. Can be negative to subtract months.
     * @return $this The updated date object.
     */
    public function addMonths(int $intMonths): static
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
     * Adds a given number of years to the current date object.
     *
     * @param int $intYears The number of years to add. Can be positive or negative.
     * @return $this The updated date object.
     */
    public function addYears(int $intYears): static
    {
        $this->Year += $intYears;
        return $this;
    }

    /**
     * Modifies the current DateTime object by adding or subtracting an interval.
     *
     * This function applies the modification to the current instance using
     * the specified value and retains the updated state of the object.
     *
     * @param mixed $mixValue The value representing the modification. This can
     *                         be a string, interval, or other formats supported
     *                         by the DateTime class.
     *
     * @return DateTime The modified DateTime object with the applied changes.
     * @throws DateMalformedStringException
     */
    public function modify(mixed $mixValue): DateTime
    {
        parent::modify($mixValue);
        return $this;
    }

    /**
     * Converts the current object into a JavaScript Date object representation.
     *
     * This method generates a string that, when executed as JavaScript, creates a new `Date` object.
     * The exact format of the string depends on whether the date or time components are null.
     *
     * @return string The JavaScript code to construct a `Date` object, based on the current object's date and time values.
     * @throws Caller
     * @throws InvalidCast
     */
    public function toJsObject(): string
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
     * Serializes the current object into a JSON representation.
     *
     * This method generates an associative array that represents the object's date and time values.
     * Depending on whether the date or time components are null, the resulting array includes only
     * the relevant data.
     *
     * @return array An associative array containing the serialized date and time values. The array also
     * includes a type identifier for JSON deserialization.
     * @throws Caller
     * @throws InvalidCast
     */
    public function jsonSerialize(): array
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
     * Magic method to retrieve the value of various properties of the object.
     *
     * This method provides read-only access to properties such as Month, Day, Year, Hour, Minute, Second,
     * Timestamp, Age, and others by extracting relevant information or performing calculations based on the object's date and time values.
     *
     * @param string $strName The name of the property to retrieve.
     *
     * @return string|QDateTime|int|null The value of the requested property. This can vary depending on the property:
     *               - int|null for properties like Month, Day, Year, Hour, Minute, Second, etc.
     *               - int for Timestamp (Unix timestamp).
     *               - string for Age (e.g., "5 hours ago" or "2 days from now").
     *               - int for FirstDayOfTheMonth or LastDayOfTheMonth.
     *               - null if the requested property is null due to unset date or time components.
     *
     * @throws DateMalformedStringException
     * @throws Caller
     * @throws UndefinedProperty If the requested property ($strName) does not exist.
     */
    public function __get(string $strName): string|QDateTime|int|null
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
     * Magic method to set the value of a property dynamically.
     *
     * This method allows setting various properties such as 'Month', 'Day', 'Year', 'Hour', 'Minute',
     * 'Second', and 'Timestamp' by handling the provided value dynamically. Depending on the property,
     * the value gets validated, cast, and applied to the current object. Special cases are handled if
     * the date or time components are null, throwing exceptions when necessary.
     *
     * @param string $strName Name of the property to set.
     * @param mixed $mixValue The value to assign to the property. The value is cast to the appropriate type depending on the property.
     *
     * @return void The value that was set for the property or null if the property was set to null.
     * @throws Caller If setting a property on a null date or time without using the appropriate method.
     * @throws InvalidCast If the value cannot be cast to the required type for the property.
     * @throws UndefinedProperty If attempting to set a property that is not defined.
     */
    public function __set(string $strName, mixed $mixValue): void
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
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate(parent::format('Y'), $mixValue, parent::format('d'));
                    return;

                case 'Day':
                    if ($this->blnDateNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Day property on a null date.  Use SetDate().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnDateNull = true;
                        $this->reinforceNullProperties();
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate(parent::format('Y'), parent::format('m'), $mixValue);
                    return;

                case 'Year':
                    if ($this->blnDateNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Year property on a null date.  Use SetDate().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnDateNull = true;
                        $this->reinforceNullProperties();
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setDate($mixValue, parent::format('m'), parent::format('d'));
                    return ;

                case 'Hour':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Hour property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime($mixValue, parent::format('i'), parent::format('s'));
                    return;

                case 'Minute':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Minute property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime(parent::format('H'), $mixValue, parent::format('s'));
                    return;

                case 'Second':
                    if ($this->blnTimeNull && (!is_null($mixValue))) {
                        throw new Caller('Cannot set the Second property on a null time.  Use SetTime().');
                    }
                    if (is_null($mixValue)) {
                        $this->blnTimeNull = true;
                        $this->reinforceNullProperties();
                        return;
                    }
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    parent::setTime(parent::format('H'), parent::format('i'), $mixValue);
                    return;

                case 'Timestamp':
                    $mixValue = Type::cast($mixValue, Type::INTEGER);
                    $this->setTimestamp($mixValue);
                    $this->blnDateNull = false;
                    $this->blnTimeNull = false;
                    return;

                default:
                    throw new UndefinedProperty('SET', 'DateTime', $strName);
            }
        } catch (InvalidCast $objExc) {
            $objExc->incrementOffset();
            throw $objExc;
        }
    }
}