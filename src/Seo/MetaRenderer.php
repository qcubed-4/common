<?php
    namespace QCubed\Seo;

    /**
     * Render <head> meta tags from PageMeta.
     *
     * Notes:
     * - We escape using htmlspecialchars to preserve UTF-8 characters (ÕÄÖÜ etc.)
     * - Indentation is handled here (NOT in templates), using PageMeta::$indentNumber
     */
    final class MetaRenderer
    {
        /**
         * Escape for HTML attribute/text context while preserving UTF-8 characters.
         */
        private static function esc(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        /**
         * Indent string (spaces). PageMeta::$indentNumber is treated as the number of spaces.
         */
        private static function indent(PageMeta $meta): string
        {
            $n = ($meta->indentNumber ?? 0);
            return $n > 0 ? str_repeat(' ', $n) : '';
        }

        /**
         * Prefix a tag with indentation.
         */
        private static function line(string $tag, string $indent): string
        {
            return $indent . $tag;
        }

        /**
         * Generates and returns HTML meta-tags based on the provided page metadata.
         */
        public static function render(PageMeta $meta): string
        {
            $indent = self::indent($meta);
            $lines = [];

            // --- Title ---
            if (trim($meta->title) !== '') {
                $lines[] = self::line('<title>' . self::esc($meta->title) . '</title>', $indent);
            }

            // --- Standard description ---
            if (trim($meta->description) !== '') {
                $lines[] = self::line('<meta name="description" content="' . self::esc($meta->description) . '">', $indent);
            }

            // --- Canonical ---
            if (!empty($meta->canonicalUrl)) {
                $lines[] = self::line('<link rel="canonical" href="' . self::esc($meta->canonicalUrl) . '">', $indent);
            }

            // --- Robots ---
            if (!empty($meta->robots)) {
                $lines[] = self::line('<meta name="robots" content="' . self::esc($meta->robots) . '">', $indent);
                $lines[] = self::line('<meta name="googlebot" content="' . self::esc($meta->robots) . '">', $indent);
            }

            // --- Apple / App name ---
            if (!empty($meta->appleMobileWebAppTitle)) {
                $lines[] = self::line('<meta name="apple-mobile-web-app-title" content="' . self::esc($meta->appleMobileWebAppTitle) . '">', $indent);
            }

            // --- Apple touch icons ---
            if (!empty($meta->appleTouchIcons)) {
                foreach ($meta->appleTouchIcons as $icon) {
                    if (!is_array($icon)) {
                        continue;
                    }

                    $href = isset($icon['href']) ? trim($icon['href']) : '';
                    if ($href === '') {
                        continue;
                    }

                    $sizes = isset($icon['sizes']) ? trim($icon['sizes']) : '';
                    $attrSizes = ($sizes !== '') ? ' sizes="' . self::esc($sizes) . '"' : '';

                    $lines[] = self::line('<link rel="apple-touch-icon"' . $attrSizes . ' href="' . self::esc($href) . '">', $indent);
                }
            }

            // --- Favicons (SVG -> PNG -> ICO) ---
            if (!empty($meta->faviconSvg)) {
                $lines[] = self::line('<link rel="icon" type="image/svg+xml" href="' . self::esc($meta->faviconSvg) . '">', $indent);
            }

            if (!empty($meta->faviconPng)) {
                foreach ($meta->faviconPng as $icon) {
                    if (!is_array($icon)) {
                        continue;
                    }

                    $href = isset($icon['href']) ? trim($icon['href']) : '';
                    if ($href === '') {
                        continue;
                    }

                    $type = isset($icon['type']) && trim($icon['type']) !== ''
                        ? trim($icon['type'])
                        : 'image/png';

                    $sizes = isset($icon['sizes']) ? trim($icon['sizes']) : '';
                    $attrSizes = ($sizes !== '') ? ' sizes="' . self::esc($sizes) . '"' : '';

                    $lines[] = self::line('<link rel="icon" type="' . self::esc($type) . '"' . $attrSizes . ' href="' . self::esc($href) . '">', $indent);
                }
            }

            if (!empty($meta->faviconIco)) {
                $lines[] = self::line('<link rel="shortcut icon" href="' . self::esc($meta->faviconIco) . '">', $indent);
            }

            // --- Manifest (PWA) ---
            if (!empty($meta->manifestUrl)) {
                $lines[] = self::line('<link rel="manifest" href="' . self::esc($meta->manifestUrl) . '">', $indent);
            }

            // --- Open Graph ---
            if (trim($meta->ogTitle) !== '') {
                $lines[] = self::line('<meta property="og:title" content="' . self::esc($meta->ogTitle) . '">', $indent);
            }

            if (trim($meta->ogDescription) !== '') {
                $lines[] = self::line('<meta property="og:description" content="' . self::esc($meta->ogDescription) . '">', $indent);
            }

            if (!empty($meta->ogImage)) {
                $lines[] = self::line('<meta property="og:image" content="' . self::esc($meta->ogImage) . '">', $indent);
            }

            if (!empty($meta->ogUrl)) {
                $lines[] = self::line('<meta property="og:url" content="' . self::esc($meta->ogUrl) . '">', $indent);
            }

            if (!empty($meta->ogType)) {
                $lines[] = self::line('<meta property="og:type" content="' . self::esc($meta->ogType) . '">', $indent);
            }

            if (!empty($meta->ogSiteName)) {
                $lines[] = self::line('<meta property="og:site_name" content="' . self::esc($meta->ogSiteName) . '">', $indent);
            }

            // --- Twitter ---
            if (trim($meta->twitterCard) !== '') {
                $lines[] = self::line('<meta name="twitter:card" content="' . self::esc($meta->twitterCard) . '">', $indent);
            }

            if (trim($meta->twitterTitle) !== '') {
                $lines[] = self::line('<meta name="twitter:title" content="' . self::esc($meta->twitterTitle) . '">', $indent);
            }

            if (trim($meta->twitterDescription) !== '') {
                $lines[] = self::line('<meta name="twitter:description" content="' . self::esc($meta->twitterDescription) . '">', $indent);
            }

            if (!empty($meta->twitterImage)) {
                $lines[] = self::line('<meta name="twitter:image" content="' . self::esc($meta->twitterImage) . '">', $indent);
            }

            // If a client has no X/Twitter account -> these will be null -> not rendered
            if (!empty($meta->twitterSite)) {
                $lines[] = self::line('<meta name="twitter:site" content="' . self::esc($meta->twitterSite) . '">', $indent);
            }

            if (!empty($meta->twitterCreator)) {
                $lines[] = self::line('<meta name="twitter:creator" content="' . self::esc($meta->twitterCreator) . '">', $indent);
            }

            // --- Theme color (optional) ---
            if (method_exists($meta, 'getThemeColor') && $meta->getThemeColor()) {
                $lines[] = self::line('<meta name="theme-color" content="' . self::esc($meta->getThemeColor()) . '">', $indent);
            }

            // IMPORTANT:
            // If your template has indentation before the PHP echo statement, the very first line
            // may get "template spaces" in addition to our indent. The cleanest fix is to place
            // the PHP block at the beginning of the line in header.inc.php.

            return implode("\n", $lines) . "\n";
        }

        /**
         * Helper for robots content.
         */
        public static function robots(bool $index = true, bool $follow = true): string
        {
            return ($index ? 'index' : 'noindex') . ',' . ($follow ? 'follow' : 'nofollow');
        }
    }
