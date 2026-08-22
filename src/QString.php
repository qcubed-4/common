<?php
    /**
     *
     * Part of the QCubed PHP framework.
     *
     * @license MIT
     *
     */

    namespace QCubed;

    use DOMDocument;
    use DOMElement;
    use DOMXPath;
    use QCubed\Exception\Caller;


    /**
     * Abstract class QString
     *
     * Utility methods for handling and manipulating strings.
     * This class should not be instantiated; all methods are static.
     */

    abstract class QString
    {
        const string LETTERS_NUMBERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        const string LETTERS_NUMBERS_SYMBOLS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}/?><,.;:~';
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
         * Performs a regular-expression replacement and guarantees a string result.
         *
         * @param string $pattern The regular expression pattern.
         * @param string $replacement The replacement string.
         * @param string $subject The input string.
         * @return string The resulting string after replacement.
         * @throws \RuntimeException If the regular expression cannot be processed.
         */
        private static function regexReplace(string $pattern, string $replacement, string $subject): string
        {
            $result = preg_replace($pattern, $replacement, $subject);

            if ($result === null) {
                throw new \RuntimeException(
                    sprintf('Regular expression failed: %s (%s)', $pattern, preg_last_error_msg())
                );
            }

            return $result;
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
            if (str_contains($strString, '<') || str_contains($strString, '&')) {
                $strString = str_replace(']]>', ']]]]><![CDATA[>', $strString);
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
            if ($str1 === '' || $str2 === '') {
                return '';
            }

            self::initQCubedEncoding();
            $encoding = self::$qcubedEncoding;

            $arrStr1 = $encoding !== null ? mb_str_split($str1, 1, $encoding) : str_split($str1);
            $arrStr2 = $encoding !== null ? mb_str_split($str2, 1, $encoding) : str_split($str2);
            $str1Len = count($arrStr1);
            $str2Len = count($arrStr2);

            $CSL = array_fill(0, $str1Len, array_fill(0, $str2Len, 0));
            $intLargestSize = 0;
            $ret = [];

            for ($i = 0; $i < $str1Len; $i++) {
                for ($j = 0; $j < $str2Len; $j++) {
                    if ($arrStr1[$i] === $arrStr2[$j]) {
                        $CSL[$i][$j] = ($i === 0 || $j === 0)
                            ? 1
                            : $CSL[$i - 1][$j - 1] + 1;

                        if ($CSL[$i][$j] > $intLargestSize) {
                            $intLargestSize = $CSL[$i][$j];
                            $ret = [];
                        }

                        if ($CSL[$i][$j] === $intLargestSize) {
                            $ret[] = implode('', array_slice($arrStr1, $i - $intLargestSize + 1, $intLargestSize));
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
            $decoded = base64_decode(strtr($s, '-_', '+/'), true);

            return $decoded === false ? '' : $decoded;
        }

        /**
         * Normalizes common Unicode punctuation characters to their ASCII equivalents.
         *
         * This method is useful for technical strings such as URLs, slugs, filenames,
         * identifiers, or other values where consistent ASCII punctuation is preferred.
         *
         * @param string $strString The input string to normalize.
         * @return string The string with supported Unicode punctuation replaced by ASCII equivalents.
         */
        public static function normalizeAsciiPunctuation(string $strString): string
        {
            return strtr($strString, [
                '–' => '-',   // En dash
                '—' => '-',   // Em dash
                '−' => '-',   // Minus sign
                '“' => '"',   // Left double quotation mark
                '”' => '"',   // Right double quotation mark
                '„' => '"',   // Double low-9 quotation mark
                '‘' => "'",   // Left single quotation mark
                '’' => "'",   // Right single quotation mark
                '…' => '...', // Horizontal ellipsis
            ]);
        }

        /**
         * Sanitizes a string to create a URL-safe representation by performing various cleanup and transformation steps.
         *
         * @param string $strString The input string to be sanitized.
         * @param int|null $intMaxLength Optional maximum length for the sanitized string. If specified, the string will be truncated to this length.
         * @return string The sanitized, URL-safe string.
         */
        public static function sanitizeForUrl(string $strString = '', ?int $intMaxLength = null): string
        {
            // Step 1: Remove all HTML tags from the string.
            $strString = strip_tags($strString);

            // Step 2: Preserve percent-encoded octets and clean up invalid % symbols.
            $strString = self::regexReplace('/%([a-fA-F0-9][a-fA-F0-9])/', '--$1--', $strString); // Preserve percent-encoded octets.
            $strString = str_replace('%', '', $strString); // Strip out stray % symbols.
            $strString = self::regexReplace('/--([a-fA-F0-9][a-fA-F0-9])--/', '%$1', $strString); // Restore valid percent-encoded octets.

            // Step 3: Remove accents/diacritical marks from international characters.
            $strString = self::removeAccents($strString);

            // Step 4: Convert the string to lowercase to ensure uniformity.
            $strString = mb_convert_case($strString, MB_CASE_LOWER, 'UTF-8');

            // Step 5: Remove HTML entities and normalize special characters.
            $strString = self::regexReplace('/&.+?;/', '', $strString); // Remove encoded HTML entities like &amp;.
            $strString = str_replace(['.', '::'], '-', $strString); // Replace dots and double colons with a dash.
            $strString = self::normalizeAsciiPunctuation($strString); // Normalize Unicode punctuation to ASCII equivalents.

            // Step 6: Replace spaces and trim unwanted characters.
            $strString = self::regexReplace('/\s+/', '-', $strString); // Replace spaces with dashes.
            $strString = self::regexReplace('|[\p{Ps}\p{Pe}\p{Pi}\p{Pf}\p{Po}\p{S}\p{Z}\p{C}\p{No}]+|u', '', $strString); // Remove unwanted punctuation, symbols, or control chars.

            // Step 7: Remove duplicated dashes and trim from both ends.
            $strString = self::regexReplace('/-+/', '-', $strString); // Collapse multiple dashes into a single dash.
            $strString = trim($strString, '-'); // Remove leading/trailing dashes.

            // Step 8: Truncate the string if a maximum length is specified.
            if ($intMaxLength !== null) {
                self::initQCubedEncoding();
                $strString = mb_substr($strString, 0, $intMaxLength, self::$qcubedEncoding ?? 'UTF-8');
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
         * @throws Caller If the specified length is less than 1 or the character set is empty.
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
            if ($strName === '') {
                return '';
            }

            return trim(self::regexReplace('/([a-z\d])([A-Z])|([A-Za-z])(\d)|(\d)([A-Za-z])/', '$1$3$5 $2$4$6', $strName));
        }

        /**
         * Retrieves the first character of the given string, using the specified encoding if defined.
         *
         * @param string $strString The input string from which the first character is to be extracted.
         * @return string|null The first character of the string, or null if the string is empty.
         */
        final public static function firstCharacter(string $strString): ?string
        {
            if ($strString === '') {
                return null;
            }

            self::initQCubedEncoding();

            return mb_substr($strString, 0, 1, self::$qcubedEncoding ?? 'UTF-8');
        }

        /**
         * Converts a camel case string into an underscored string.
         *
         * @param string $strName The input string in camel case format.
         * @return string The converted string in underscored format, or an empty string if input is empty.
         */
        public static function underscoreFromCamelCase(string $strName): string
        {
            if ($strName === '') {
                return '';
            }

            return strtolower(self::regexReplace('/([a-z\d])([A-Z])/', '$1_$2', $strName));
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
            if ($intSize === false || $intSize === 0) {
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
        public static function obfuscateEmail(string $strEmail): string
        {
            $strEmail = QString::htmlEntities($strEmail);
            $strEmail = str_replace('@', '<strong style="display: none;">' . md5(microtime()) . '</strong>&#064;<strong style="display: none;">' . md5(microtime()) . '</strong>', $strEmail);
            return str_replace('.', '<strong style="display: none;">' . md5(microtime()) . '</strong>&#046;<strong style="display: none;">' . md5(microtime()) . '</strong>', $strEmail);
        }

        /**
         * Render one or multiple email addresses with lightweight harvesting protection (frontend output).
         *
         * What it does:
         * - Shows the email address immediately as normal, copyable text (good UX).
         * - Does NOT output any `mailto:` link in the HTML source initially.
         * - Stores the address split into two data attributes (no "@" in attributes):
         *     - data-eu = email user part (before "@")
         *     - data-ed = email domain part (after "@")
         * - Inserts harmless HTML comment fragments around "@" and "." in the *visible text*
         *   (e.g. `info<!-- -->@<!-- -->firma<!-- -->.<!-- -->ee`) to break simple regex-based
         *   scrapers scanning raw HTML source. Browsers still render it as `info@firma.ee`,
         *   and copy/paste remains normal.
         *
         * JavaScript setup (required for `mailto:`):
         * Add the following code ONCE to your global JS bundle (e.g. main.js). It uses event
         * delegation, so it automatically works for all links produced by this function.
         *
         * ```JavaScript
         * document.addEventListener("pointerdown", (e) => {
         *   const a = e.target.closest("a[data-eu][data-ed]");
         *   if (a) a.href = `mailto:${a.dataset.eu}@${a.dataset.ed}`;
         * }, { passive: true });
         *
         * document.addEventListener("keydown", (e) => {
         *   if (e.key !== "Enter" && e.key !== " ") return;
         *   const a = e.target.closest("a[data-eu][data-ed]");
         *   if (a) a.href = `mailto:${a.dataset.eu}@${a.dataset.ed}`;
         * });
         * ```
         *
         * Input formats:
         * - Single email string: "info@firma.ee"
         * - Comma-separated list (typical DB format): "info@firma.ee, support@firma.ee"
         * - Array of emails: ["info@firma.ee", "support@firma.ee"]
         *
         * Notes:
         * - We intentionally DO NOT place the full email into aria-label/title attributes,
         *   because some scrapers read those attributes too. The visible text is already
         *   readable for users.
         * - If a value is malformed, it is output as escaped plain text.
         *
         * Example usage:
         * ```php
         * echo QString::renderEmails($dbRow['emails'], ' | ');
         * // where $dbRow['emails'] = "info@firma.ee, support@firma.ee"
         * ```
         *
         * @param null|string|array $items One email, a comma-separated list, or an array of emails.
         * @param string $separator Separator used between multiple rendered emails.
         *
         * @return string HTML markup containing protected email links.
         */
        public static function renderEmails(string|array|null $items, string $separator = ', '): string
        {
            if ($items === null) {
                return '';
            }

            // DB is stored as a comma-separated string; also accept arrays for convenience.
            if (!is_array($items)) {
                $items = trim($items);
                $items = ($items === '')
                    ? []
                    : array_filter(array_map('trim', explode(',', $items)), static fn($x) => $x !== '');
            }

            if (empty($items)) {
                return '';
            }

            $out = [];

            foreach ($items as $email) {
                $email = trim((string)$email);
                if ($email === '') {
                    continue;
                }

                $pos = strrpos($email, '@');
                if ($pos === false || $pos === 0 || $pos >= strlen($email) - 1) {
                    // Malformed: print as plain text (escaped)
                    $out[] = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    continue;
                }

                $user = substr($email, 0, $pos);
                $domain = substr($email, $pos + 1);

                // Data attributes: no "@" in attributes, no "mailto:" anywhere
                $safeUser = htmlspecialchars($user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDomain = htmlspecialchars($domain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                // Visible text (escaped) + HTML comment obfuscation to break simple regex harvesting
                $safeVisible = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeVisible = str_replace('@', '<!-- -->@<!-- -->', $safeVisible);
                $safeVisible = str_replace('.', '<!-- -->.<!-- -->', $safeVisible);

                $out[] = "<a href=\"#\" data-eu=\"{$safeUser}\" data-ed=\"{$safeDomain}\" rel=\"nofollow noopener noreferrer\">{$safeVisible}</a>";
            }

            return implode($separator, $out);
        }

        /**
         * Obfuscate email links inside an HTML fragment for safe frontend output.
         *
         * This helper scans an HTML fragment (typically content produced by CKEditor or
         * another WYSIWYG editor) and converts email links into a safer format that
         * prevents simple email harvesting bots from scraping `mailto:` links directly
         * from the HTML source.
         *
         * The function only modifies <a> elements that:
         * - contain a native mailto: link, or
         * - already display a plain email address as link text.
         *
         * All other HTML content is left untouched.
         *
         * The function is intended for frontend rendering only, not for database storage.
         * This is important because editors such as CKEditor may strip or modify custom
         * attributes during editing and saving.
         *
         * @param string $html HTML fragment that may contain email links.
         *
         * @return string HTML fragment with email links converted to the protected format.
         */
        public static function obfuscateEmailsForFrontendHtml(string $html): string
        {
            if (trim($html) === '') {
                return $html;
            }

            if (!str_contains($html, '<a')) {
                return $html;
            }

            // Fast exit: if there is clearly nothing email-related, do nothing.
            if (
                stripos($html, 'mailto:') === false &&
                stripos($html, '@') === false &&
                stripos($html, 'data-eu=') === false
            ) {
                return $html;
            }

            $doc = new DOMDocument('1.0', 'UTF-8');

            // Wrap the fragment so we can safely extract only inner HTML later.
            $wrapped = '<div>' . $html . '</div>';

            // Important:
            // Force DOMDocument to interpret the fragment as UTF-8.
            $wrapped = '<?xml encoding="utf-8" ?>' . $wrapped;

            $prev = libxml_use_internal_errors(true);
            $loaded = $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            if (!$loaded) {
                return $html;
            }

            $xpath = new DOMXPath($doc);

            /** @var DOMElement $a */
            foreach ($xpath->query('//a') as $a) {
                // Already safe -> keep it, only normalize href
                if ($a->hasAttribute('data-eu') && $a->hasAttribute('data-ed')) {
                    $a->setAttribute('href', '#');
                    continue;
                }

                $href = trim((string)$a->getAttribute('href'));
                $text = trim((string)$a->textContent);

                $email = null;

                // Case 1: native mailto:
                if (stripos($href, 'mailto:') === 0) {
                    $candidate = html_entity_decode(substr($href, 7), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $email = trim(explode('?', $candidate, 2)[0]);
                }
                // Case 2: the visible link text itself is an email
                elseif ($text !== '' && filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $email = $text;
                }

                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $pos = strrpos($email, '@');
                if ($pos === false || $pos === 0 || $pos >= strlen($email) - 1) {
                    continue;
                }

                $user = substr($email, 0, $pos);
                $domain = substr($email, $pos + 1);

                $a->setAttribute('href', '#');
                $a->setAttribute('data-eu', $user);
                $a->setAttribute('data-ed', $domain);
                $a->setAttribute('rel', 'nofollow noopener noreferrer');
            }

            // Extract only the inner HTML of our wrapper div.
            $div = $doc->getElementsByTagName('div')->item(0);
            if (!$div) {
                return $html;
            }

            $out = '';
            foreach ($div->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }

            return $out;
        }

        /**
         * Returns true if string contains characters other than standard ASCII letters, numbers, or hyphens.
         */
        public static function containsInvalidCharacters(string $str): bool
        {
            return (bool)preg_match('/[^a-zA-Z0-9-]/', $str);
        }

        /**
         * Returns true if a string contains any non-ASCII letter, number or hyphen.
         * (So string is only a-z, 0-9, -)
         */
        public static function containsNonCharacters(string $str): bool
        {
            return (bool)preg_match('/[^a-z0-9-]/i', $str);
        }

        /**
         * Normalizes a given URL by verifying its format and adding necessary prefixes
         * if they are missing. Handles protocols such as http, https, mailto, and tel.
         *
         * @param string|null $url The input URL to be normalized. Can be null.
         *
         * @return string|null The normalized URL or null if the input is empty.
         */
        public static function normalizeUrl(?string $url): ?string
        {
            if ($url === null) {
                return null;
            }

            $url = trim($url);

            if ($url === '') {
                return null;
            }

            if (preg_match('~^https?://~i', $url)) {
                return $url;
            }

            if (preg_match('~^mailto:|^tel:~i', $url)) {
                return $url;
            }

            return 'https://' . $url;
        }
    }