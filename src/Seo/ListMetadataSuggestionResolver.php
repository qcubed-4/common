<?php
    namespace QCubed\Seo;

    use MetadataSuggestions;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;

    /**
     * Class ListMetadataSuggestionResolver
     */
    final class ListMetadataSuggestionResolver
    {
        public static int $defaultMax = 500;
        public static int $defaultSlack = 0;

        public static string $defaultSeparator = ' | ';
        public static string $defaultLanguageCode = 'et';

        /**
         * Resolves a description only if the current description is empty.
         * If the current description is non-empty, it returns an empty string
         * to indicate no overwrite is needed. Otherwise, it generates and
         * returns a suggested description.
         *
         * @param string|null $currentDescription The existing description. If non-empty, it remains unchanged.
         * @param int $contentTypeId The content type identifier to generate a suggestion.
         * @param string|null $languageCode The optional language code for generating the description.
         * @param string|null $prefix The optional prefix for the generated description.
         * @param string|null $separator The optional separator used in the generated description.
         * @param int|null $max The optional maximum length of the generated description.
         * @param int|null $slack The optional slack length for additional flexibility.
         *
         * @return string Returns an empty string if the current description is not empty,
         *                otherwise returns a suggested description.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function resolveOnlyIfEmpty(
            ?string $currentDescription,
            int $contentTypeId,
            ?string $languageCode = null,
            ?string $prefix = null,
            ?string $separator = null,
            ?int $max = null,
            ?int $slack = null
        ): string {
            $manual = self::cleanText($currentDescription);

            // If already filled, do not overwrite (return empty so the caller can skip saving)
            if ($manual !== '') {
                return '';
            }

            // Otherwise generate suggestion
            return self::resolveDescription(
                $currentDescription,
                $contentTypeId,
                $languageCode,
                $prefix,
                $separator,
                $max,
                $slack
            );
        }

        /**
         * Resolves and finalizes the description based on the provided parameters.
         * If a current description is provided, it is cleaned and used. Otherwise,
         * a suggested description is fetched and processed based on the content type
         * and language code.
         *
         * @param string|null $currentDescription The current description, if available.
         * @param int $contentTypeId The identifier for the content type.
         * @param string|null $languageCode The language code for localization, if applicable.
         * @param string|null $prefix A prefix to prepend to the finalized description, if any.
         * @param string|null $separator A custom separator to use during formatting.
         * @param int|null $max The maximum length allowed for the description.
         * @param int|null $slack The additional buffer or slack allowed during length formatting.
         *
         * @return string The finalized description based on the provided parameters.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function resolveDescription(
            ?string $currentDescription,
            int $contentTypeId,
            ?string $languageCode = null,
            ?string $prefix = null,
            ?string $separator = null,
            ?int $max = null,
            ?int $slack = null
        ): string {
            $separator = $separator ?? self::$defaultSeparator;
            $max = $max ?? self::$defaultMax;
            $slack = $slack ?? self::$defaultSlack;

            $languageCode = self::cleanText($languageCode) ?: self::$defaultLanguageCode;

            // If the editor has written something, use it
            $manual = self::cleanText($currentDescription);
            if ($manual !== '') {
                return self::finalize($manual, $prefix, $separator, $max, $slack);
            }

            // Otherwise fetch suggestion by contentTypeId + language
            $suggested = self::getSuggestedDescription($contentTypeId, $languageCode);
            if ($suggested !== '') {
                return self::finalize($suggested, $prefix, $separator, $max, $slack);
            }

            return '';
        }

        /**
         * Applies a description if the current description is empty. If a valid current description
         * is provided, it is cleaned and finalized without being overwritten. Otherwise, a new
         * description is generated based on the given parameters.
         *
         * @param string|null $currentDescription The current description to evaluate.
         * @param int $contentTypeId The identifier for the content type.
         * @param string|null $languageCode The language code to use for localization, if applicable.
         * @param string|null $prefix A prefix to prepend to the finalized description, if any.
         * @param string|null $separator A custom separator to use during formatting.
         * @param int|null $max The maximum allowed length for the description.
         * @param int|null $slack Extra buffer allowed beyond the maximum length.
         *
         * @return string The finalized or generated description depending on the inputs.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function applyIfEmpty(
            ?string $currentDescription,
            int $contentTypeId,
            ?string $languageCode = null,
            ?string $prefix = null,
            ?string $separator = null,
            ?int $max = null,
            ?int $slack = null
        ): string {
            $manual = self::cleanText($currentDescription);

            // If already filled, keep it (do not overwrite)
            if ($manual !== '') {
                $separator = $separator ?? self::$defaultSeparator;
                $max = $max ?? self::$defaultMax;
                $slack = $slack ?? self::$defaultSlack;

                return self::finalize($manual, $prefix, $separator, $max, $slack);
            }

            // Otherwise generate suggestion
            return self::resolveDescription(
                $currentDescription,
                $contentTypeId,
                $languageCode,
                $prefix,
                $separator,
                $max,
                $slack
            );
        }

        /**
         * Retrieves a suggested description based on the content type and language code.
         *
         * @param int $contentTypeId The identifier for the content type.
         * @param string|null $languageCode The language code for localization, if applicable.
         *
         * @return string The suggested description based on the provided content type and language code.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function getSuggestedDescriptionOnly(
            int $contentTypeId,
            ?string $languageCode = null
        ): string {
            $languageCode = self::cleanText($languageCode) ?: self::$defaultLanguageCode;
            return self::getSuggestedDescription($contentTypeId, $languageCode);
        }

        /**
         * Retrieves a suggested description based on the content type ID and language code.
         * The description is extracted from metadata suggestions and cleaned before returning.
         *
         *  Requires UNIQUE(content_type_id, language_code)
         *  so QCubed-4 generates loadByContentTypeIdLanguageCode().
         *
         * @param int $contentTypeId The identifier of the content type for which the description is suggested.
         * @param string $languageCode The language code used to localize the suggested description.
         *
         * @return string The cleaned suggested description, or an empty string if no suggestion is found.
         * @throws Caller
         * @throws InvalidCast
         */
        private static function getSuggestedDescription(int $contentTypeId, string $languageCode): string
        {
            $row = MetadataSuggestions::loadByContentTypeIdLanguageCode($contentTypeId, $languageCode);
            if (!$row) {
                return '';
            }

            return self::cleanText($row->Description);
        }

        /**
         * Finalizes the given text by applying a prefix, separator, and length restrictions.
         * Cleans the base text and prefix, appends the prefix if necessary, and trims
         * the text to a word boundary within the specified length constraints.
         *
         * @param string $baseText The main text to be finalized.
         * @param string|null $prefix An optional prefix to prepend to the base text.
         * @param string $separator The separator to use between the prefix and the base text.
         * @param int $max The maximum length allowed for the finalized text.
         * @param int $slack An additional buffer length allowed beyond the maximum limit.
         *
         * @return string The finalized text adhering to the specified conditions.
         */
        private static function finalize(
            string $baseText,
            ?string $prefix,
            string $separator,
            int $max,
            int $slack
        ): string {
            $baseText = self::cleanText($baseText);
            if ($baseText === '') {
                return '';
            }

            $prefixClean = self::cleanText($prefix);
            if ($prefixClean !== '' && !self::startsWith($baseText, $prefixClean)) {
                $baseText = $prefixClean . $separator . $baseText;
            }

            return self::trimToWordBoundary($baseText, $max + $slack);
        }

        /**
         * Cleans and sanitizes a given text by removing HTML tags, decoding HTML entities,
         * replacing multiple whitespace characters with a single space, and trimming the result.
         *
         * @param string|null $text The text to be cleaned. Null values will return an empty string.
         *
         * @return string The cleaned and sanitized version of the input text.
         */
        private static function cleanText(?string $text): string
        {
            if ($text === null) {
                return '';
            }

            $text = strip_tags($text);
            $text = html_entity_decode(
                $text,
                ENT_QUOTES | ENT_HTML5,
                defined('QCUBED_ENCODING') ? QCUBED_ENCODING : 'UTF-8'
            );

            $text = preg_replace('/\s+/u', ' ', $text);
            return trim((string)$text);
        }

        /**
         * Trims the given text to the specified character limit, ensuring it does not
         * cut off in the middle of a word. If trimming results in an empty string,
         * it falls back to a hard cut at the limit.
         *
         * @param string $text The input text to be trimmed.
         * @param int $limit The maximum number of characters allowed, ensuring words are not split.
         *
         * @return string The trimmed text, respecting word boundaries when possible.
         */
        private static function trimToWordBoundary(string $text, int $limit): string
        {
            $text = trim($text);
            if ($text === '') {
                return '';
            }

            if (mb_strlen($text) <= $limit) {
                return $text;
            }

            $cut = mb_substr($text, 0, $limit);
            $cut = preg_replace('/\s+\S*$/u', '', $cut);
            $cut = trim((string)$cut);

            return $cut !== '' ? $cut : trim(mb_substr($text, 0, $limit));
        }

        /**
         * Determines whether the given string starts with the specified prefix.
         * The comparison is case-insensitive and trims both the input string
         * and the prefix before evaluation.
         *
         * @param string $haystack The string to check.
         * @param string $needle The prefix to look for.
         *
         * @return bool Returns true if the haystack starts with the needle, otherwise false.
         */
        private static function startsWith(string $haystack, string $needle): bool
        {
            $haystack = trim($haystack);
            $needle = trim($needle);

            if ($needle === '') {
                return true;
            }

            return mb_strtolower(mb_substr($haystack, 0, mb_strlen($needle))) === mb_strtolower($needle);
        }
    }

    /** Examples of use cases on the backend side */

    /**
     *  $languageCode = 'en'; // Temporarily manually
     *
     * $this->txtDescription->Text = ListMetadataSuggestionResolver::applyIfEmpty(
     * $this->txtDescription->Text,
     * 1, // Content type ID
     * $languageCode
     * );
     *
     * $desc = trim($this->txtDescription->Text);
     *
     * $this->txtDescription->Text = $desc;
     *
     * $this->objMetadata->setDescription($desc);
     * $this->objMetadata->save();
     *
     * $this->objFrontendLinks->setMetadataDescription($desc);
     * $this->objFrontendLinks->save();
     *
     */