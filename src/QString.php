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
use QCubed\Project\Application;

/**
 * Class QString
 *
 * String utilities
 * @was QString
 */
abstract class QString
{
    const LETTERS_NUMBERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const LETTERS_NUMBERS_SYMBOLS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}/?><,.;:~';

    /**
     * This faux constructor method throws a caller exception.
     * The String object should never be instantiated, and this constructor
     * override simply guarantees it.
     *
     * @throws Caller
     */
    final public function __construct()
    {
        throw new Caller('String should never be instantiated. All methods and variables are publicly statically accessible.');
    }

    /**
     * Returns the last character of a given string, or null if the given
     * string is null.
     *
     * @param string $strString
     *
     * @return string the last character, or null
     */
    final public static function lastCharacter($strString)
    {
        if (defined('QCUBED_ENCODING')) {
            $intLength = mb_strlen($strString, QCUBED_ENCODING);
            if ($intLength > 0) {
                return mb_substr($strString, $intLength - 1, 1, QCUBED_ENCODING);
            } else {
                return null;
            }
        } else {
            $intLength = strlen($strString);
            if ($intLength > 0) {
                return $strString[$intLength - 1];
            } else {
                return null;
            }
        }
    }

    /**
     * Checks whether a given string starts with another (sub)string
     *
     * @param string $strHaystack
     * @param string $strNeedle
     *
     * @return bool
     */
    final public static function startsWith($strHaystack, $strNeedle)
    {
        // If the length of needle is greater than the length of haystack, then return false
        if (defined('QCUBED_ENCODING')) {
            if (mb_strlen($strNeedle, QCUBED_ENCODING) > mb_strlen($strHaystack, QCUBED_ENCODING)) {
                // To suppress the error in strpos function below
                return false;
            }
        } else {
            if (strlen($strNeedle) > strlen($strHaystack)) {
                // To suppress the error in strpos function below
                return false;
            }
        }

        // search backwards starting from haystack length characters from the end
        return $strNeedle === "" || strrpos($strHaystack, $strNeedle, -strlen($strHaystack)) !== false;
    }

    /**
     * Checks whether a given string ends with another (sub)string
     *
     * @param string $strHaystack
     * @param string $strNeedle
     *
     * @return bool
     */
    final public static function endsWith($strHaystack, $strNeedle)
    {
        // If the length of needle is greater than the length of haystack, then return false
        if (defined('QCUBED_ENCODING')) {
            if (mb_strlen($strNeedle, QCUBED_ENCODING) > mb_strlen($strHaystack, QCUBED_ENCODING)) {
                // To suppress the error in strpos function below
                return false;
            }
        } else {
            if (strlen($strNeedle) > strlen($strHaystack)) {
                // To suppress the error in strpos function below
                return false;
            }
        }

        // search forward starting from end minus needle length characters
        return $strNeedle === "" || strpos($strHaystack, $strNeedle, strlen($strHaystack) - strlen($strNeedle)) !== false;
    }

    /**
     * Truncates the string to a given length, adding ellipses (if needed).
     *
     * @param string $strText string to truncate
     * @param integer $intMaxLength the maximum possible length of the string to return (including length of the ellipse)
     *
     * @return string the full string or the truncated string with ellipse
     */
    final public static function truncate($strText, $intMaxLength)
    {
        if (mb_strlen($strText, QCUBED_ENCODING) > $intMaxLength) {
            return mb_substr($strText, 0, $intMaxLength - 3, QCUBED_ENCODING) . "...";
        } else {
            return $strText;
        }
    }

    /**
     * Escapes the string so that it can be safely used in as an Xml Node (basically, adding CDATA if needed)
     *
     * @param string $strString string to escape
     *
     * @return string the XML Node-safe String
     */
    final public static function xmlEscape($strString)
    {
        if ((mb_strpos($strString, '<', 0, QCUBED_ENCODING) !== false) ||
            (mb_strpos($strString, '&', 0, QCUBED_ENCODING) !== false)
        ) {
            $strString = str_replace(']]>', ']]]]><![CDATA[>', $strString);
            $strString = sprintf('<![CDATA[%s]]>', $strString);
        }

        return $strString;
    }

    /**
     * Finds longest substring which is common among two strings
     *
     * @param string $str1
     * @param string $str2
     *
     * @return string
     */
    final public static function longestCommonSubsequence($str1, $str2)
    {
        // Replace null values with empty strings
        $str1 = $str1 ?? '';
        $str2 = $str2 ?? '';

        if (defined('QCUBED_ENCODING')) {
            $str1Len = mb_strlen($str1, QCUBED_ENCODING);
            $str2Len = mb_strlen($str2, QCUBED_ENCODING);
        } else {
            $str1Len = strlen($str1);
            $str2Len = strlen($str2);
        }

        if ($str1Len == 0 || $str2Len == 0) {
            return '';
        }

        $CSL = array(); // Common Sequence Length array
        $intLargestSize = 0;
        $ret = array();

        // initialize the CSL array to assume there are no similarities
        for ($i = 0; $i < $str1Len; $i++) {
            $CSL[$i] = array();
            for ($j = 0; $j < $str2Len; $j++) {
                $CSL[$i][$j] = 0;
            }
        }

        for ($i = 0; $i < $str1Len; $i++) {
            for ($j = 0; $j < $str2Len; $j++) {
                // check every combination of characters
                if ($str1[$i] == $str2[$j]) {
                    // these are the same in both strings
                    if ($i == 0 || $j == 0) { // it's the first character, so it's clearly only 1 character long
                        $CSL[$i][$j] = 1;
                    } else { // it's one character longer than the string from the previous character
                        $CSL[$i][$j] = $CSL[$i - 1][$j - 1] + 1;
                    }

                    if ($CSL[$i][$j] > $intLargestSize) {
                        // remember this as the largest
                        $intLargestSize = $CSL[$i][$j];
                        // wipe any previous results
                        $ret = array();
                        // and then fall through to remember this new value
                    }
                    if ($CSL[$i][$j] == $intLargestSize) { // remember the largest string(s)
                        $ret[] = substr($str1, $i - $intLargestSize + 1, $intLargestSize);
                    }
                }
                // else, $CSL should be set to 0, which it was already initialized to
            }
        }
        // return the first match
        if (count($ret) > 0) {
            return $ret[0];
        } else {
            return '';
        } // no matches
    }

    /**
     * Base64 encode in a way that the result can be passed through HTML forms and URLs.
     * @param $s
     * @return mixed
     */
    public static function base64UrlSafeEncode($s)
    {
        $s = base64_encode($s);
        $s = str_replace('+', '-', $s);
        $s = str_replace('/', '_', $s);
        $s = str_replace('=', '', $s);
        return ($s);
    }

    /**
     * Base64 Decode in a way that the result can be passed through HTML forms and URLs.
     *
     * @param $s
     * @return mixed
     */
    public static function base64UrlSafeDecode($s)
    {
        $s = str_replace('_', '/', $s);
        $s = str_replace('-', '+', $s);
        $s = base64_decode($s);
        return ($s);
    }

/**
     * Generates a string usable in a URL from any Utf8 encoded string, commonly called a slug.
     *
     * @param string $strString Input string in utf8
     * @param integer|null $intMaxLength The maximum possible length of the string to return. The result will be truncated to this length.
     *
     * @return string The slug-generated string
     */
    public static function sanitizeForUrl($strString = '', $intMaxLength = null)
    {
        $strString = strip_tags($strString);
        $strString = preg_replace('/%([a-fA-F0-9][a-fA-F0-9])/', '--$1--', $strString); // Preserve escaped octets
        $strString = str_replace('%', '', $strString); // Remove percent signs that are not part of an octet
        $strString = preg_replace('/--([a-fA-F0-9][a-fA-F0-9])--/', '%$1', $strString);  // Restore octets

        $strString = self::removeAccents($strString);

        $strString = mb_convert_case($strString, MB_CASE_LOWER, "UTF-8");
        $strString = preg_replace('/&.+?;/', '', $strString); // Remove entities
        $strString = str_replace(['.', '::'], '-', $strString);

        // Remove excess spaces and special characters
        $strString = preg_replace('/\s+/', '-', $strString); // Remove excess spaces
        $strString = preg_replace('|[\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Po}\p{S}\p{Z}\p{C}\p{No}]+|u', '', $strString); // Remove unwanted characters

        $strString = preg_replace('/-+/', '-', $strString); // Remove excess hyphens
        $strString = trim($strString, '-');

        if ($intMaxLength !== null) {
            $strString = mb_substr($strString, 0, $intMaxLength, QCUBED_ENCODING);
        }

        return rtrim($strString, '-'); // Remove trailing hyphen
    }

    /**
     * Remove accents from a string
     *
     * @param string $strString Input string
     * @return string String without accents
     */
    public static function removeAccents($strString)
    {
        if (!preg_match('/[\x80-\xff]/', $strString)) {
            return $strString;
        }

        if (self::isUtf8($strString)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A', chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
                chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A', chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
                chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E', chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
                chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I', chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N',
                chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O', chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
                chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U', chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
                chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y', chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a',
                chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a', chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
                chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c', chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
                chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e', chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
                chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i', chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
                chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o', chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
                chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u', chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
                chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y', chr(195) . chr(191) => 'y',
                // Decompositions for Latin Extended-A
                chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a', chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
                chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a', chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
                chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c', chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
                chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c', chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
                chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd', chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
                chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e', chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
                chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e', chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
                chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g', chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
                chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g', chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
                chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h', chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
                chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i', chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
                chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i', chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
                chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i', chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
                chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j', chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
                chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L', chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
                chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L', chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
                chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L', chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
                chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N', chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
                chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N', chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
                chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o', chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
                chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o', chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
                chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r', chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
                chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r', chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
                chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's', chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
                chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's', chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
                chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't', chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
                chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u', chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
                chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u', chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
                chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u', chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
                chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w', chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
                chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z', chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
                chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z', chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
                // Euro Sign
                chr(226) . chr(130) . chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194) . chr(163) => ''
            );
            $strString = strtr($strString, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
                . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
                . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
                . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
                . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
                . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
                . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
                . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
                . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
                . chr(252) . chr(253) . chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $strString = strtr($strString, $chars['in'], $chars['out']);
            $double_chars['in'] = array(
                chr(140),
                chr(156),
                chr(198),
                chr(208),
                chr(222),
                chr(223),
                chr(230),
                chr(240),
                chr(254)
            );
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $strString = str_replace($double_chars['in'], $double_chars['out'], $strString);
        }
        return $strString;
    }

    /**
     * Check whether a string is utf 8 or not
     *
     * @param string $strString Input string
     * @return bool True if the string is UTF-8, false otherwise
     */
    public static function isUtf8($strString)
    {
        return preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]             # ASCII
            | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $strString);
    }

    /**
     * Get a random string of a given length
     *
     * @param int $intLength Length of the string which is to be produced
     * @param int|string $strCharacterSet Character Set to be used
     *
     * @return string The generated random string
     * @throws Caller
     */
    public static function getRandomString($intLength, $strCharacterSet = self::LETTERS_NUMBERS)
    {
        // Ensure intLength is an integer
        $intLength = Type::cast($intLength, Type::INTEGER);
        // Ensure strCharacterSet is a string
        $strCharacterSet = Type::cast($strCharacterSet, Type::STRING);

        if ($intLength < 1) {
            throw new Caller('Cannot generate a random string of zero length');
        }

        if (strlen(trim($strCharacterSet)) == 0) {
            throw new Caller('Character set must contain at least 1 printable character.');
        }

        // Generate and return the random string
        return substr(
            str_shuffle(
                str_repeat($strCharacterSet, ceil($intLength / strlen($strCharacterSet)))
            ), 0, $intLength
        );
    }

    /**
     * Replaces underscores with spaces and makes the first character of each word uppercase
     *
     * @param string $strName String which has to be converted into single words
     *
     * @return string The resulting string (as words)
     */
    public static function wordsFromUnderscore($strName)
    {
        $strToReturn = trim(str_replace('_', ' ', $strName));
        if (strtolower($strToReturn) == $strToReturn) {
            return ucwords($strToReturn);
        }
        return $strToReturn;
    }

    /**
     * Converts a CamelCased word into separate words
     *
     * @param string $strName String to be converted
     *
     * @return string Resulting set of words derived from camel case
     */
    public static function wordsFromCamelCase($strName)
    {
        if (strlen($strName) == 0) {
            return '';
        }

        $strToReturn = self::firstCharacter($strName);

        for ($intIndex = 1; $intIndex < strlen($strName); $intIndex++) {
            $strChar = substr($strName, $intIndex, 1);
            $strPrevChar = substr($strName, $intIndex - 1, 1);

            if (ctype_upper($strChar) || (ctype_digit($strChar) && !ctype_digit($strPrevChar)) || (!ctype_digit($strChar) && ctype_digit($strPrevChar))) {
                $strToReturn .= ' ' . $strChar;
            } else {
                $strToReturn .= $strChar;
            }
        }

        return $strToReturn;
    }

    /**
     * Returns the first character of a given string, or null if the given string is null.
     *
     * @param string $strString
     *
     * @return string|null the first character, or null
     */
    final public static function firstCharacter($strString)
    {
        if (defined('QCUBED_ENCODING')) {
            return mb_substr($strString, 0, 1, QCUBED_ENCODING) ?: null;
        } else {
            return strlen($strString) ? $strString[0] : null;
        }
    }

    /**
     * Given a CamelCased word, returns the underscored version
     *
     * @param string $strName CamelCased word
     *
     * @return string Underscored word
     */
    public static function underscoreFromCamelCase($strName)
    {
        if (strlen($strName) == 0) {
            return '';
        }

        $strToReturn = strtolower($strName[0]);

        for ($intIndex = 1; $intIndex < strlen($strName); $intIndex++) {
            $strChar = $strName[$intIndex];
            $strToReturn .= ctype_upper($strChar) ? '_' . strtolower($strChar) : $strChar;
        }

        return $strToReturn;
    }

    /**
     * Returns a javaCase word given an underscore word
     *
     * @param string $strName The underscored word
     *
     * @return string The word in javaCase
     */
    public static function javaCaseFromUnderscore($strName)
    {
        $strToReturn = self::camelCaseFromUnderscore($strName);
        return lcfirst($strToReturn);
    }

    /**
     * Converts an underscored word into a CamelCased word
     *
     * @param string $strName String to be converted
     *
     * @return string The resulting camel-cased word
     */
    public static function camelCaseFromUnderscore($strName)
    {
        return str_replace('_', '', ucwords($strName, '_'));
    }

    /**
     * Global/Central HtmlEntities command to perform the PHP equivalent of htmlentities.
     *
     * @param string $strText Text string to perform HTML escaping
     * @return string The HTML escaped string
     */
    public static function htmlEntities($strText)
    {
        $strEncoding = defined('QCUBED_ENCODING') ? QCUBED_ENCODING : 'UTF-8';
        return htmlentities($strText ?? '', ENT_COMPAT | ENT_HTML5, $strEncoding);
    }

    /**
     * Generates a valid URL Query String based on values in the provided array. Uses global $_GET if no array is provided.
     *
     * @param array $arr
     * @return string
     */
    public static function generateQueryString($arr = null)
    {
        $arr = $arr ?? $_GET;
        return !empty($arr) ? '?' . http_build_query($arr) : '';
    }

    /**
     * Return true if the string is an integer. False if a float or anything else.
     *
     * @param string $strVal
     * @return bool
     */
    public static function isInteger($strVal)
    {
        return ctype_digit(strval($strVal));
    }

    /**
     * Convert file size in bytes to human-readable
     *
     * @param string $strFile
     * @param integer $intPrecision
     * @return string
     */
    public static function formatFileSize($strFile, $intPrecision = 2)
    {
        $intSize = filesize($strFile);
        $suffixes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $i = 0;
        while ($intSize > 1024 && $i < count($suffixes) - 1) {
            $intSize /= 1024;
            $i++;
        }

        return round($intSize, $intPrecision) . ' ' . $suffixes[$i];
    }
}
