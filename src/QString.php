<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

use QCubed as Q;
use QCubed\Exception\Caller;
use QCubed\Project\Application;

/**
 * Abstract class QString
 *
 * Utility methods for handling and manipulating strings.
 * This class should not be instantiated; all methods are static.
 */

abstract class QString
{
    const LETTERS_NUMBERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const LETTERS_NUMBERS_SYMBOLS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}/?><,.;:~';
    private static ?string $qcubedEncoding = null;

    /**
     * Constructs the class but is intentionally designed to prevent instantiation.
     * Throws an exception as all methods and variables are meant to be accessed
     * statically.
     *
     * @return void
     * @throws Caller If instantiation is attempted.
     */
    final public function __construct()
    {
        throw new Caller('String should never be instantiated. All methods and variables are publicly statically accessible.');
    }

    /**
     * Initializes the QCUBED_ENCODING value.
     */
    private static function initQCubedEncoding(): void
    {
        if (self::$qcubedEncoding === null) {
            self::$qcubedEncoding = defined('QCUBED_ENCODING') ? QCUBED_ENCODING : null;
        }
    }

    /**
     * Returns the last character of a given string or null if the string is empty or null.
     *
     * @param string|null $strString The input string.
     * @return string|null The last character, or null if the string is empty or null.
     */
    final public static function lastCharacter(?string $strString): ?string
    {
        if ($strString === null || $strString === '') {
            return null;
        }

        self::initQCubedEncoding();

        $encoding = self::$qcubedEncoding;
        if ($encoding) {
            return mb_substr($strString, -1, 1, $encoding);
        } else {
            return $strString[strlen($strString) - 1];
        }
    }

    /**
     * Checks whether a string starts with a given substring.
     *
     * @param string $strHaystack The main string.
     * @param string $strNeedle The substring to check.
     * @return bool True if $strHaystack starts with $strNeedle, false otherwise.
     */
    final public static function startsWith(string $strHaystack, string $strNeedle): bool
    {
        if ($strNeedle === '') {
            return true;
        }

        self::initQCubedEncoding();

        if (self::$qcubedEncoding) {
            return mb_substr($strHaystack, 0, mb_strlen($strNeedle, self::$qcubedEncoding), self::$qcubedEncoding) === $strNeedle;
        }

        return str_starts_with($strHaystack, $strNeedle);
    }

    /**
     * Checks whether a string ends with a given substring.
     *
     * @param string $strHaystack The main string.
     * @param string $strNeedle The substring to check.
     * @return bool True if $strHaystack ends with $strNeedle, false otherwise.
     */
    final public static function endsWith(string $strHaystack, string $strNeedle): bool
    {
        if ($strNeedle === '') {
            return true;
        }

        self::initQCubedEncoding();

        if (self::$qcubedEncoding) {
            return mb_substr($strHaystack, -mb_strlen($strNeedle, self::$qcubedEncoding), null, self::$qcubedEncoding) === $strNeedle;
        }

        return str_ends_with($strHaystack, $strNeedle);
    }

    /**
     * Truncates a string to a specified maximum length, optionally adding ellipses.
     *
     * @param string $strText The input string.
     * @param int $intMaxLength The maximum length of the truncated string, including ellipses.
     * @param bool $addEllipses Whether to append "..." to truncated strings (default is true).
     * @return string The truncated string.
     */
    final public static function truncate(string $strText, int $intMaxLength, bool $addEllipses = true): string
    {
        if ($intMaxLength <= 0) {
            return '';
        }

        $ellipsis = $addEllipses ? '...' : '';
        $maxTextLength = $addEllipses ? $intMaxLength - strlen($ellipsis) : $intMaxLength;

        self::initQCubedEncoding();

        if (self::$qcubedEncoding) {
            return mb_strlen($strText, self::$qcubedEncoding) > $intMaxLength
                ? (mb_substr($strText, 0, $maxTextLength, self::$qcubedEncoding) . $ellipsis)
                : $strText;
        }

        if (strlen($strText) > $intMaxLength) {
            return substr($strText, 0, $maxTextLength) . $ellipsis;
        }

        return $strText;
    }

    /**
     * Escapes a string for use in XML by enclosing it in a CDATA section if necessary.
     *
     * This method ensures that any special XML characters (like `<` or `&`) are properly escaped
     * for XML processing.
     *
     * @param string $strString The input string to escape.
     * @return string The escaped string, wrapped in a CDATA section if needed.
     */
    final public static function xmlEscape(string $strString): string
    {
        self::initQCubedEncoding();

        // Check for special characters in the string
        if (
            (self::$qcubedEncoding && (mb_strpos($strString, '<', 0, self::$qcubedEncoding) !== false || mb_strpos($strString, '&', 0, self::$qcubedEncoding) !== false)) ||
            (!self::$qcubedEncoding && (str_contains($strString, '<') || str_contains($strString, '&')))
        ) {
            $strString = str_replace(']]>', ']]]]><![CDATA[>', $strString);
            // Wrap the entire string in a CDATA section
            $strString = sprintf('<![CDATA[%s]]>', $strString);
        }

        return $strString;
    }

    /**
     * Computes the longest common subsequence (LCS) of two strings.
     * The LCS is the longest sequence that appears in both strings in the same order.
     * If either string is empty, the result will be an empty string.
     *
     * @param string $str1 The first input string for comparison. Defaults to an empty string.
     * @param string $str2 The second input string for comparison. Defaults to an empty string.
     * @return string The longest common subsequence of the two input strings.
     */
    final public static function longestCommonSubsequence(string $str1 = '', string $str2 = ''): string
    {
        // Replace null values with empty strings
        $str1 = $str1 ?? '';
        $str2 = $str2 ?? '';

        $str1Len = defined('QCUBED_ENCODING')
            ? mb_strlen($str1, QCUBED_ENCODING)
            : strlen($str1);
        $str2Len = defined('QCUBED_ENCODING')
            ? mb_strlen($str2, QCUBED_ENCODING)
            : strlen($str2);

        if ($str1Len === 0 || $str2Len === 0) {
            return '';
        }

        $CSL = array_fill(0, $str1Len, array_fill(0, $str2Len, 0));
        $intLargestSize = 0;
        $ret = [];

        for ($i = 0; $i < $str1Len; $i++) {
            for ($j = 0; $j < $str2Len; $j++) {
                if ($str1[$i] === $str2[$j]) {
                    $CSL[$i][$j] = ($i === 0 || $j === 0)
                        ? 1
                        : $CSL[$i - 1][$j - 1] + 1;

                    if ($CSL[$i][$j] > $intLargestSize) {
                        $intLargestSize = $CSL[$i][$j];
                        $ret = [];
                    }

                    if ($CSL[$i][$j] === $intLargestSize) {
                        $ret[] = substr($str1, $i - $intLargestSize + 1, $intLargestSize);
                    }
                }
            }
        }

        return $ret[0] ?? '';
    }

    /**
     * Encodes a given string into a Base64 URL-safe format by replacing specific characters
     * and removing unnecessary padding.
     *
     * @param string $s The input string to be encoded.
     * @return string The Base64 URL-safe encoded string.
     */
    public static function base64UrlSafeEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    /**
     * Decodes a Base64 URL-safe encoded string.
     *
     * @param string $s The Base64 URL-safe encoded string to decode.
     * @return string The decoded string. Returns an empty string if decoding fails.
     */
    public static function base64UrlSafeDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/')) ?: ''; // Prevent returning `false`.
    }

    /**
     * Sanitizes a string to create a URL-safe representation by performing various cleanup and transformation steps.
     *
     * @param string $strString The input string to be sanitized. Defaults to an empty string if null.
     * @param int|null $intMaxLength Optional maximum length for the sanitized string. If specified, the string will be truncated to this length.
     * @return string The sanitized, URL-safe string.
     */
    public static function sanitizeForUrl(string $strString = '', ?int $intMaxLength = null): string
    {
        // Ensure the input is a string, handle null gracefully.
        $strString = $strString ?? '';

        // Step 1: Remove all HTML tags from the string.
        $strString = strip_tags($strString);

        // Step 2: Preserve percent-encoded octets and clean up invalid % symbols.
        $strString = preg_replace('/%([a-fA-F0-9][a-fA-F0-9])/', '--$1--', $strString); // Preserve percent-encoded octets.
        $strString = str_replace('%', '', $strString); // Strip out stray % symbols.
        $strString = preg_replace('/--([a-fA-F0-9][a-fA-F0-9])--/', '%$1', $strString); // Restore valid percent-encoded octets.

        // Step 3: Remove accents/diacritical marks from international characters.
        $strString = self::removeAccents($strString);

        // Step 4: Convert the string to lowercase to ensure uniformity.
        $strString = mb_convert_case($strString, MB_CASE_LOWER, 'UTF-8');

        // Step 5: Remove HTML entities and replace some special characters (dots, colons).
        $strString = preg_replace('/&.+?;/', '', $strString); // Remove encoded HTML entities like &amp;.
        $strString = str_replace(['.', '::'], '-', $strString); // Replace dots and double colons with a dash.

        // Step 6: Replace spaces and trim unwanted characters.
        $strString = preg_replace('/\s+/', '-', $strString); // Replace spaces with dashes.
        $strString = preg_replace('|[\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Po}\p{S}\p{Z}\p{C}\p{No}]+|u', '', $strString); // Remove unwanted punctuation, symbols, or control chars.

        // Step 7: Remove duplicated dashes and trim from both ends.
        $strString = preg_replace('/-+/', '-', $strString); // Collapse multiple dashes into a single dash.
        $strString = trim($strString, '-'); // Remove leading/trailing dashes.

        // Step 8: Truncate the string if a maximum length is specified.
        if ($intMaxLength !== null && defined('QCUBED_ENCODING')) {
            $strString = mb_substr($strString, 0, $intMaxLength, QCUBED_ENCODING);
        }

        // Step 9: Ensure there are no trailing dashes left.
        return rtrim($strString, '-');
    }

    /**
     * Removes accents from a given string by replacing accented characters
     * with their non-accented counterparts. Handles UTF-8 strings and falls
     * back to ISO-8859-1 encoding if necessary.
     *
     * @param string $strString The input string from which accents should be removed.
     * @return string The string with accents removed.
     */
    public static function removeAccents(string $strString): string
    {
        // Quick check: if there are no extended characters, return early.
        if (!preg_match('/[\x80-\xff]/', $strString)) {
            return $strString;
        }

        // Handle UTF-8 characters using a decomposition map.
        if (self::isUtf8($strString)) {
            $utf8Map = [
                // Latin-1 Supplement
                'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
                'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
                'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
                'Õ' => 'O', 'Ö' => 'O', '×' => 'x', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
                'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH', 'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a',
                'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
                'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd',
                'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
                // Latin Extended-A
                'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C',
                'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c', 'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c',
                'Ď' => 'D', 'ď' => 'd', 'Đ' => 'D', 'đ' => 'd', 'Ē' => 'E', 'ē' => 'e', 'Ĕ' => 'E',
                'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E', 'ę' => 'e', 'Ě' => 'E', 'ě' => 'e',
                'Ĝ' => 'G', 'ĝ' => 'g', 'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G',
                'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h', 'Ĩ' => 'I', 'ĩ' => 'i',
                'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I', 'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'İ' => 'I',
                'ı' => 'i', 'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ķ' => 'K', 'ķ' => 'k',
                'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L', 'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l',
                'Ŀ' => 'L', 'ŀ' => 'l', 'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N',
                'ņ' => 'n', 'Ň' => 'N', 'ň' => 'n', 'ŉ' => 'n', 'Ŋ' => 'N', 'ŋ' => 'n', 'Ō' => 'O',
                'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o', 'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE', 'œ' => 'oe',
                'Ŕ' => 'R', 'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r', 'Ś' => 'S',
                'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S', 'ş' => 's', 'Š' => 'S', 'š' => 's',
                'Ţ' => 'T', 'ţ' => 't', 'Ť' => 'T', 'ť' => 't', 'Ŧ' => 'T', 'ŧ' => 't', 'Ũ' => 'U',
                'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u', 'Ů' => 'U', 'ů' => 'u',
                'Ű' => 'U', 'ű' => 'u', 'Ų' => 'U', 'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y',
                'ŷ' => 'y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z', 'Ž' => 'Z',
                'ž' => 'z', 'ƒ' => 'f'
            ];

            return strtr($strString, $utf8Map);
        }

        // If not UTF-8, assume ISO-8859-1 and fall back to simpler replacements.
        $iso88591In = "\x80\x83\x8a\x8e\x9a\x9e\x9f\xa0\xa2\xa5\xb5\xc0\xc1\xc2\xc3\xc4\xc5\xc7"
            . "\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd1\xd2\xd3\xd4\xd5\xd6\xd8\xd9\xda\xdb"
            . "\xdc\xdd\xe0\xe1\xe2\xe3\xe4\xe5\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf1"
            . "\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xff";
        $iso88591Out = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

        return strtr($strString, $iso88591In, $iso88591Out);
    }

    /**
     * Determines whether a given string is valid UTF-8 encoded.
     *
     * @param string|null $strString The input string to check. Can be null.
     * @return bool Returns true if the input string is valid UTF-8, false otherwise.
     */
    public static function isUtf8(?string $strString): bool
    {
        return $strString !== null && ($strString === '' || preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]             # ASCII
            | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%x', $strString) === 1);
    }

    /**
     * Generates a random string of a specified length using characters from the provided character set.
     *
     * @param int $intLength The desired length of the random string. Must be greater than 0.
     * @param string $strCharacterSet The set of characters to use for generating the random string.
     *                                Defaults to a pre-defined set of letters and numbers.
     *
     * @return string A random string of the specified length generated from the given character set.
     * @throws Caller If the specified length is less than 1 or the character set is empty.*@throws
     *     \QCubed\Exception\Caller
     */
    public static function getRandomString(int $intLength, string $strCharacterSet = self::LETTERS_NUMBERS): string
    {
        if ($intLength < 1) {
            throw new Caller('Cannot generate a random string of zero lengths.');
        }

        if (trim($strCharacterSet) === '') {
            throw new Caller('Character set must contain at least 1 printable character.');
        }

        return substr(
            str_shuffle(
                str_repeat($strCharacterSet, (int) ceil($intLength / strlen($strCharacterSet)))
            ), 0, $intLength
        );
    }

    /**
     * Converts a given underscore-separated string into a space-separated string
     * with proper capitalization. If the input string is in all lowercase, the result
     * will be capitalized for each word; otherwise, it is returned as-is.
     *
     * @param string $strName The underscore-separated string to transform.
     * @return string The transformed string with spaces and proper capitalization.
     */
    public static function wordsFromUnderscore(string $strName): string
    {
        $strToReturn = trim(str_replace('_', ' ', $strName));
        return strtolower($strToReturn) === $strToReturn ? ucwords($strToReturn) : $strToReturn;
    }

    /**
     * Converts a camel case formatted string into a space-separated string of words.
     *
     * @param string $strName The camel case strings to be converted.
     * @return string A space-separated string of words derived from the camel case input.
     */
    public static function wordsFromCamelCase(string $strName): string
    {
        if (empty($strName)) {
            return '';
        }

        return trim(preg_replace('/([a-z\d])([A-Z])|([A-Za-z])(\d)|(\d)([A-Za-z])/', '$1$3$5 $2$4$6', $strName));
    }

    /**
     * Retrieves the first character of the given string, using the specified encoding if defined.
     *
     * @param string $strString The input string from which the first character is to be extracted.
     * @return string|null The first character of the string, or null if the string is empty.
     */
    final public static function firstCharacter(string $strString): ?string
    {
        return mb_substr($strString, 0, 1, defined('QCUBED_ENCODING') ? QCUBED_ENCODING : 'UTF-8') ?: null;
    }

    /**
     * Converts a camel case string into an underscored string.
     *
     * @param string $strName The input string in camel case format.
     * @return string The converted string in underscored format, or an empty string if input is empty.
     */
    public static function underscoreFromCamelCase(string $strName): string
    {
        if (empty($strName)) {
            return '';
        }

        return strtolower(preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $strName));
    }

    /**
     * Converts a snake_case string to camelCase by removing underscores and capitalizing
     * the first letter of each word after an underscore.
     *
     * @param string $strName The snake case strings to be converted.
     * @return string The resulting camelCase string.
     */
    public static function camelCaseFromUnderscore(string $strName): string
    {
        if ($strName === '') {
            return '';
        }

        // Convert words to uppercase and remove underscores
        return str_replace('_', '', ucwords($strName, '_'));
    }

    /**
     * Converts a string from underscore case to Java-style camelCase,
     * ensuring the first letter of the resulting string is lowercase.
     *
     * @param string $strName The input string in underscore_case format.
     * @return string The converted string in Java-style camelCase format.
     */
    public static function javaCaseFromUnderscore(string $strName): string
    {
        if ($strName === '') {
            return '';
        }

        // Use camelCase conversion and make the first letter lowercase
        return lcfirst(self::camelCaseFromUnderscore($strName));
    }


    /**
     * Converts a given string to its HTML entities representation, ensuring proper encoding
     * and handling of special characters.
     *
     * @param string|null $strText The input string to be converted. If null, an empty string is used.
     * @return string The HTML entities encoded string.
     */
    public static function htmlEntities(?string $strText): string
    {
        // Let's define the encoding
        $strEncoding = defined('QCUBED_ENCODING') ? QCUBED_ENCODING : 'UTF-8';

        // Converting HTML entities
        return htmlentities($strText ?? '', ENT_QUOTES | ENT_HTML5, $strEncoding);
    }

    /**
     * Generates a query string from the given array or the global $_GET array if no array is provided.
     *
     * @param array|null $arr An associative array of query parameters. If null, the global $_GET array is used.
     * @return string The generated query string, starting with a '?' if parameters are present, or an empty string if none.
     */
    public static function generateQueryString(?array $arr = null): string
    {
        $arr = $arr ?? $_GET;
        return (!empty($arr)) ? '?' . http_build_query($arr) : '';
    }

    /**
     * Determines if a given value is an integer by checking if it is numeric and contains only digit characters.
     *
     * @param mixed $strVal The value to be checked.
     * @return bool True if the value is an integer, otherwise false.
     */
    public static function isInteger(mixed $strVal): bool
    {
        return is_numeric($strVal) && ctype_digit((string) $strVal);
    }

    /**
     * Formats the file size of a given file into a human-readable string using appropriate units.
     *
     * @param string $strFile The path to the file whose size is to be formatted.
     * @param int $intPrecision The number of decimal places to include in the formatted size. Defaults to 2.
     * @return string The formatted file size as a string, including the appropriate unit (e.g., "KB", "MB").
     */
    public static function formatFileSize(string $strFile, int $intPrecision = 2): string
    {
        if (!file_exists($strFile)) {
            return '0 bytes';
        }

        $intSize = filesize($strFile);
        if ($intSize === 0) {
            return '0 bytes';
        }

        $suffixes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $base = floor(log($intSize, 1024));
        $size = $intSize / pow(1024, $base);

        return round($size, max(0, $intPrecision)) . ' ' . $suffixes[$base];
    }

    /**
     * Obfuscates an email so that it can be outputted as HTML to the page.
     * @param string $strEmail the email address to obfuscate
     * @return string the HTML of the obfuscated Email address
     */
    public  static function obfuscateEmail(string $strEmail): string {
        $strEmail = QString::htmlEntities($strEmail);
        $strEmail = str_replace('@', '<strong style="display: none;">' . md5(microtime()) . '</strong>&#064;<strong style="display: none;">' . md5(microtime()) . '</strong>', $strEmail);
        return str_replace('.', '<strong style="display: none;">' . md5(microtime()) . '</strong>&#046;<strong style="display: none;">' . md5(microtime()) . '</strong>', $strEmail);
    }

    /**
     * Renders an obfuscated email link and generates the necessary JavaScript to decode
     * and display it on the client-side upon a page load.
     *
     * @param string $email The email address to be obfuscated and rendered.
     * @param string|null $controlId Optional control ID to be used for the anchor element.
     *                               If not provided, a unique ID will be generated.
     * @return string The HTML anchor element with the obfuscated email, ready to be decoded and displayed.
     * @throws Caller
     */
    public static function renderObfuscatedEmail(string $email, ?string $controlId = null): string
    {
        $encodedEmail = self::base64UrlSafeEncode($email);
        $id = $controlId ?: 'obf_email_' . uniqid();

        $js = <<<EOT
        document.addEventListener("DOMContentLoaded", function() {
            var element = document.getElementById("$id");
            if (element) {
                var decodedEmail = atob("$encodedEmail".replace(/-/g, '+').replace(/_/g, '/'));
                element.href = "mailto:" + decodedEmail;
                element.textContent = decodedEmail;
            }
        })
    EOT;
        Application::executeJavaScript($js, Q\ApplicationBase::PRIORITY_HIGH);

        return "<a href='#' id='$id' rel='nofollow noopener noreferrer'></a>";
    }
}