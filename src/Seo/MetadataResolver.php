<?php
    namespace QCubed\Seo;


    /**
     * Class MetadataResolver
     */
    final class MetadataResolver
    {
        public static int $defaultMin = 120;
        public static int $defaultMax = 220;
        public static int $defaultSlack = 40;

        public static string $defaultSeparator = ' | ';

        /**
         * Regular expression pattern used to match the end of a sentence.
         * It identifies punctuation marks (., !, or ?) followed by whitespace
         * and ensures the next character after the whitespace is an uppercase letter.
         * The 'u' modifier enables Unicode matching.
         *
         *  Sentence end = punctuation + whitespace + next is an uppercase letter (Unicode).
         *  Supports: . ! ?
         */
        public static string $defaultSentenceEndRegex = '/([.!?])\s+(?=\p{Lu})/u';


        /**
         * Resolves and constructs a description based on multiple input parameters including
         * manual overrides, lead text, content, title, prefix, and formatting constraints.
         * Automatically selects and formats an appropriate description while respecting
         * constraints on length and sentence boundaries.
         *
         * @param string|null $manualDescription An optional manually provided description that takes precedence.
         * @param string|null $lead An optional lead text used as a primary source for generating the description.
         * @param string|null $content The main content body used as a fallback to generate the description.
         * @param string|null $title An optional title to be included in the description, especially when no primary text is available.
         * @param string|null $prefix An optional prefix to prepend to the description, often used for customization.
         * @param string|null $separator A string separating the prefix, title, and content in the constructed description.
         * @param int|null $min The minimum desired character length for the resolved description.
         * @param int|null $max The maximum allowed character length for the resolved description.
         * @param int|null $slack An additional buffer to the maximum length for sentence-based trimming.
         * @param string|null $sentenceEndRegex A regex pattern defining valid sentence-ending markers for trimming purposes.
         *
         *  Priority:
         *  1) manualDescription (editor override)
         *  2) lead
         *  3) content
         *  4) title
         *
         *  Optional:
         *  - prefix + separator + title + (sentence based from lead/content)
         *
         * @return string A formatted and resolved description that satisfies the constraints provided.
         */
        public static function resolveDescription(
            ?string $manualDescription,
            ?string $lead,
            ?string $content,
            ?string $title = null,
            ?string $prefix = null,
            ?string $separator = null,
            ?int $min = null,
            ?int $max = null,
            ?int $slack = null,
            ?string $sentenceEndRegex = null
        ): string {
            $separator = $separator ?? self::$defaultSeparator;
            $min = $min ?? self::$defaultMin;
            $max = $max ?? self::$defaultMax;
            $slack = $slack ?? self::$defaultSlack;
            $sentenceEndRegex = $sentenceEndRegex ?? self::$defaultSentenceEndRegex;

            // 1) manual override wins
            $manualDescription = self::cleanText($manualDescription);
            if ($manualDescription !== '') {
                return self::trimToWordBoundary($manualDescription, $max + $slack);
            }

            // 2) base text
            $leadClean = self::cleanText($lead);
            $contentClean = self::cleanText($content);
            $titleClean = self::cleanText($title);

            $baseText = $leadClean !== '' ? $leadClean : $contentClean;
            if ($baseText === '') {
                $baseText = $titleClean;
            }

            // 3) Prefix formatting (Abbreviation of the organization name | Title ...)
            $prefixClean = self::cleanText($prefix);
            if ($prefixClean !== '') {
                $final = $prefixClean;

                if ($titleClean !== '') {
                    $final .= $separator . $titleClean;
                }

                // Append meaningful content only if available
                if ($baseText !== '' && $baseText !== $titleClean) {
                    $final .= $separator . $baseText;
                }

                $baseText = $final;
            }

            // 4) sentence-based build
            $result = self::buildSentenceBased($baseText, $min, $max, $slack, $sentenceEndRegex);

            // 5) final safety (never too long, never mid-word)
            return self::trimToWordBoundary($result, $max + $slack);
        }

        /**
         * Cleans a given text string by removing HTML tags, decoding HTML entities,
         * normalizing whitespace, and trimming leading or trailing spaces.
         *
         * @param string|null $text The input text to be cleaned. It may be null.
         *
         * @return string A cleaned and normalized string. Returns an empty string if the input is null.
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
         * Trims a given text string to a specified character limit without cutting off words
         * in the middle. Ensures the returned text remains meaningful and avoids partial words
         * at the boundary. Falls back to hard trim if removing partial words results in an
         * empty string.
         *
         * @param string $text The input text to be trimmed to the word boundary.
         * @param int $limit The maximum allowed character length for the trimmed text.
         *
         * @return string The trimmed text that respects the character limit and word boundaries.
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

            // remove partial last word
            $cut = preg_replace('/\s+\S*$/u', '', $cut);
            $cut = trim((string)$cut);

            // if the cut became empty, fallback hard cut
            if ($cut === '') {
                return trim(mb_substr($text, 0, $limit));
            }

            return $cut;
        }

        /**
         * Builds a description based on sentence boundaries, adhering to given minimum and
         * maximum length constraints. Tries to include as many sentences as possible within
         * the specified limits, while ensuring coherent and meaningful output.
         *
         * @param string $text The input text to be processed into a sentence-based description.
         * @param int $min The minimum desired character length for the output description.
         * @param int $max The maximum allowed character length for the output description.
         * @param int $slack An additional buffer to the maximum length to allow flexibility in sentence trimming.
         * @param string $sentenceEndRegex A regex pattern used to identify sentence-ending markers in the text.
         *
         *  Sentence-based building:
         *  - collect sentences until >= min
         *  - stop at sentence boundary <= max+slack
         *  - if the next sentence exceeds max+slack, keep previous if >= min
         *
         * @return string A sentence-based description trimmed to meet the constraints of minimum, maximum, and slack length.
         */
        private static function buildSentenceBased(
            string $text,
            int $min,
            int $max,
            int $slack,
            string $sentenceEndRegex
        ): string {
            $text = trim($text);
            if ($text === '') {
                return '';
            }

            // if already within max -> keep as is
            if (mb_strlen($text) <= $max) {
                return $text;
            }

            $sentences = self::splitIntoSentences($text, $sentenceEndRegex);

            // If no sentence splitting possible, fallback to the word boundary cut
            if (count($sentences) <= 1) {
                return self::trimToWordBoundary($text, $max);
            }

            $result = '';

            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }

                $candidate = ($result === '') ? $sentence : ($result . ' ' . $sentence);
                $candidateLen = mb_strlen($candidate);

                // Always build up until we reach min
                if ($candidateLen < $min) {
                    $result = $candidate;
                    continue;
                }

                // If a candidate is within max+slack, accept and stop
                if ($candidateLen <= ($max + $slack)) {
                    $result = $candidate;
                    break;
                }

                // Candidate would exceed max+slack
                // If we already have a valid result >= min, stop before exceeding
                if ($result !== '' && mb_strlen($result) >= $min) {
                    break;
                }

                // Otherwise fallback
                return self::trimToWordBoundary($text, $max);
            }

            if ($result === '') {
                return self::trimToWordBoundary($text, $max);
            }

            return $result;
        }

        /**
         * Splits a given text into an array of sentences based on sentence-ending
         * delimiters and specific boundary rules. The method ensures sentence structure
         * preservation, considers specialized rules for determining sentence boundaries,
         * and processes text segmentation properly.
         *
         * @param string $text The input text to be split into sentences. It is trimmed before processing.
         * @param string $sentenceEndRegex A regex pattern that identifies sentence-ending markers
         *                                  (e.g., periods, exclamation marks, or question marks).
         *
         * @return array An array of sentences extracted from the provided text. If the input text is
         *               empty or cannot be split, a single-element array containing the original text
         *               will be returned.
         */
        private static function splitIntoSentences(string $text, string $sentenceEndRegex): array
        {
            $text = trim($text);
            if ($text === '') {
                return [''];
            }

            $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            if (!$parts) {
                return [$text];
            }

            $sentences = [];
            $buffer = '';

            $count = count($parts);
            for ($i = 0; $i < $count; $i++) {
                $token = $parts[$i];

                if ($buffer === '') {
                    $buffer = $token;
                } else {
                    $buffer .= ' ' . $token;
                }

                // Candidate: buffer ends with. ! ?
                if (!preg_match('/[.!?]$/u', $buffer)) {
                    continue;
                }

                // End if no next token
                if ($i + 1 >= $count) {
                    $sentences[] = trim($buffer);
                    $buffer = '';
                    continue;
                }

                $nextToken = $parts[$i + 1];

                // 1) Standard rule: next starts with an uppercase letter => boundary
                if (preg_match('/^\p{Lu}/u', $nextToken)) {
                    $sentences[] = trim($buffer);
                    $buffer = '';
                    continue;
                }

                // 2) Number-start sentence support (date / ordinal / plain number)
                if (self::isValidSentenceStartToken($nextToken)) {
                    $sentences[] = trim($buffer);
                    $buffer = '';
                }

                // Otherwise: do not split here
            }

            if (trim($buffer) !== '') {
                $sentences[] = trim($buffer);
            }

            return $sentences ?: [$text];
        }

        /**
         * Validates whether a given token is suitable to be the start of a sentence.
         * The validation is based on several criteria, including whether the token
         * starts with an uppercase letter, a valid numeral, or specific date and
         * ordinal formats.
         *
         * @param string $token The token to be validated as a potential sentence start.
         *                      It is expected to be a non-empty string after trimming.
         *
         * @return bool Returns true if the token meets the criteria for starting a sentence,
         *              otherwise returns false.
         */
        private static function isValidSentenceStartToken(string $token): bool
        {
            $token = trim($token);
            if ($token === '') {
                return false;
            }

            // If starts with an uppercase letter => yes
            if (preg_match('/^\p{Lu}/u', $token)) {
                return true;
            }

            // If it does not start with a digit = >, no (we only validate digit cases here)
            if (!preg_match('/^\d/u', $token)) {
                return false;
            }

            // Date: 14.04.2025 or 14.04.25
            if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}\b/u', $token)) {
                return true;
            }

            // Ordinal: 2. or 10.
            if (preg_match('/^\d+\.\b/u', $token)) {
                return true;
            }

            // Plain number: 245
            if (preg_match('/^\d+\b/u', $token)) {
                return true;
            }

            return false;
        }
    }

    /** Examples of use cases on the backend side */

    /**
     * NEWS example
     *
     * $desc = MetadataResolver::resolveDescription(
     *      $objMetadata->getDescription(),
     *      $objNews->getLead(),
     *      $objNews->getContent(),
     *      $objNews->getTitle()
     * );
     *
     */

    /**
     * GALLERY (prefix + separator flexible)
     *
     * $desc = MetadataResolver::resolveDescription (
     *      $objMetadata->getDescription(),
     *      null,
     *      null,
     *      $objGallery->getTitle(),
     *      prefix: 'Gallery',
     *      separator: ' | '
     * );
     *
     * OR
     *
     * $desc = MetadataResolver::resolveDescription (
     *      $objMetadata->getDescription(),
     *      null,
     *      null,
     *      $objGallery->getTitle(),
     *      prefix: 'Gallery',
     *      separator: ': '
     * );
     *
     */