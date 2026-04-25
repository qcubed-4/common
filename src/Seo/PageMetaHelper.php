<?php
    namespace QCubed\Seo;

    /**
     * Build a PageMeta object from already prepared data.
     *
     * Responsibility:
     * - assemble page-level metadata for frontend rendering
     * - read stored values from FrontendLinks / Metadata / options
     * - populate OG / Twitter / canonical / robots / favicon-related fields
     *
     * Non-responsibility:
     * - does NOT generate meta-descriptions from content text
     * - does NOT call MetadataResolver
     *
     * Expected data flow:
     * - MetadataResolver is used in a backend/admin when metadata description is generated or edited
     * - generated/final description is saved into Metadata.description
     * - FrontendLinks.metadata_description may be synced from the same stored value
     * - PageMetaHelper only reads already prepared values
     */
    final class PageMetaHelper
    {
        /**
         * Build a basic PageMeta object.
         *
         * Priority rules:
         * - title:
         *   1) options['title']
         *   2) FrontendLinks title
         *   3) content title
         *   4) "Untitled"
         *
         * - description:
         *   1) options['description']
         *   2) FrontendLinks.metadata_description
         *   3) Metadata.description
         *   4) empty string
         *
         * - image:
         *   1) options['imageUrl']
         *   2) options['imageResolver'] callback
         *   3) options['defaultImage']
         *
         * @param object $frontendLink FrontendLinks-like object.
         * @param object|null $metadata Metadata-like object.
         * @param object|null $content Content object (News, Article, Event, Gallery, etc).
         * @param array $options Optional overrides:
         *   - title (string|null)
         *   - description (string|null)
         *   - imageUrl (string|null)
         *   - imageResolver (callable|null) function($content, $frontendLink): ?string
         *   - defaultImage (string|null)
         *
         * @return PageMeta
         */
        public static function build(
            object $frontendLink,
            ?object $metadata = null,
            ?object $content = null,
            array $options = []
        ): PageMeta {
            $meta = new PageMeta();

            // -------------------------
            // Title
            // -------------------------
            $titleOverride = $options['title'] ?? null;

            if (is_string($titleOverride) && trim($titleOverride) !== '') {
                $meta->title = trim($titleOverride);
            } elseif (method_exists($frontendLink, 'getTitle') && trim((string)$frontendLink->getTitle()) !== '') {
                $meta->title = trim((string)$frontendLink->getTitle());
            } elseif ($content && method_exists($content, 'getTitle') && trim((string)$content->getTitle()) !== '') {
                $meta->title = trim((string)$content->getTitle());
            } else {
                $meta->title = 'Untitled';
            }

            // -------------------------
            // Description
            // -------------------------
            $meta->description = self::resolveStoredDescription($frontendLink, $metadata, $options);

            // OG defaults
            $meta->ogTitle = $meta->title;
            $meta->ogDescription = $meta->description;

            // -------------------------
            // Image
            // -------------------------
            $imageUrl = $options['imageUrl'] ?? null;

            if (is_string($imageUrl) && trim($imageUrl) !== '') {
                $meta->ogImage = trim($imageUrl);
            } else {
                $resolver = $options['imageResolver'] ?? null;
                if (is_callable($resolver)) {
                    $resolved = $resolver($content, $frontendLink);
                    if (is_string($resolved) && trim($resolved) !== '') {
                        $meta->ogImage = trim($resolved);
                    }
                }
            }

            $defaultImage = $options['defaultImage'] ?? null;
            if ((empty($meta->ogImage) || trim((string)$meta->ogImage) === '') && is_string($defaultImage) && trim($defaultImage) !== '') {
                $meta->ogImage = self::toAbsoluteUrl(trim($defaultImage));
            }

            // Twitter defaults
            if (!$meta->ogImage) {
                $meta->twitterCard = 'summary';
            }

            $meta->twitterTitle = $meta->ogTitle;
            $meta->twitterDescription = $meta->ogDescription;
            $meta->twitterImage = $meta->ogImage;

            return $meta;
        }

        /**
         * Build a full PageMeta object, including canonical, robots,
         * OG site fields, Twitter account fields, favicon defaults and PWA metadata.
         *
         * @param object $frontendLink FrontendLinks-like object.
         * @param object|null $metadata Metadata-like object.
         * @param object|null $content Content object.
         * @param string|null $absoluteUrl Absolute page URL for canonical and og:url.
         * @param string|null $siteName Site name for og:site_name and apple-mobile-web-app-title.
         * @param string|null $twitterSite Twitter/X handle, e.g. "@portal".
         * @param array $options Extra options:
         *   - title (string|null)
         *   - description (string|null)
         *   - imageUrl (string|null)
         *   - imageResolver (callable|null)
         *   - defaultImage (string|null)
         *   - ogType (string|null)
         *   - isDetail (bool|null)
         *   - robotsIndex (bool|null)
         *   - robotsFollow (bool|null)
         *   - twitterCreator (string|null)
         *   - faviconBasePath (string|null)
         *   - iconBasePath (string|null) legacy alias
         *   - defaultFavicon (string|null) legacy alias
         *
         * @return PageMeta
         */
        public static function buildFull(
            object $frontendLink,
            ?object $metadata = null,
            ?object $content = null,
            ?string $absoluteUrl = null,
            ?string $siteName = null,
            ?string $twitterSite = null,
            array $options = []
        ): PageMeta {
            $meta = self::build($frontendLink, $metadata, $content, $options);

            // canonical + og:url
            $absoluteUrl = is_string($absoluteUrl) ? trim($absoluteUrl) : null;
            if (!empty($absoluteUrl)) {
                $meta->canonicalUrl = $absoluteUrl;
                $meta->ogUrl = $absoluteUrl;
            }

            // og:site_name
            $siteName = is_string($siteName) ? trim($siteName) : null;
            if (!empty($siteName)) {
                $meta->ogSiteName = $siteName;
            }

            // robots
            $robotsIndex = $options['robotsIndex'] ?? true;
            $robotsFollow = $options['robotsFollow'] ?? true;
            $meta->robots = self::buildRobots((bool)$robotsIndex, (bool)$robotsFollow);

            // og:type
            $ogTypeOverride = $options['ogType'] ?? null;
            if (is_string($ogTypeOverride) && trim($ogTypeOverride) !== '') {
                $meta->ogType = trim($ogTypeOverride);
            } else {
                $isDetail = $options['isDetail'] ?? null;
                if ($isDetail === null) {
                    $isDetail = ($content !== null);
                }
                $meta->ogType = $isDetail ? 'article' : 'website';
            }

            // twitter:site
            $twitterSite = is_string($twitterSite) ? trim($twitterSite) : null;
            if (!empty($twitterSite)) {
                $meta->twitterSite = self::normalizeTwitterHandle($twitterSite);
            }

            // twitter:creator
            $twitterCreator = $options['twitterCreator'] ?? null;
            if (is_string($twitterCreator) && trim($twitterCreator) !== '') {
                $meta->twitterCreator = self::normalizeTwitterHandle(trim($twitterCreator));
            }

            // favicon / manifest / apple touch icons defaults
            $faviconBase = $options['faviconBasePath']
                ?? ($options['iconBasePath']
                    ?? ($options['defaultFavicon'] ?? null));

            if (!is_string($faviconBase) || trim($faviconBase) === '') {
                $faviconBase = '/frontend/assets/favicon';
            }

            $faviconBase = rtrim($faviconBase, '/');

            if (property_exists($meta, 'appleMobileWebAppTitle')
                && (empty($meta->appleMobileWebAppTitle) || trim((string)$meta->appleMobileWebAppTitle) === '')
                && !empty($siteName)
            ) {
                $meta->appleMobileWebAppTitle = $siteName;
            }

            if (property_exists($meta, 'manifestUrl')
                && (empty($meta->manifestUrl) || trim((string)$meta->manifestUrl) === '')
            ) {
                $meta->manifestUrl = $faviconBase . '/site.webmanifest';
            }

            if (property_exists($meta, 'faviconSvg')
                && (empty($meta->faviconSvg) || trim((string)$meta->faviconSvg) === '')
            ) {
                $meta->faviconSvg = $faviconBase . '/favicon.svg';
            }

            if (property_exists($meta, 'faviconPng')
                && (empty($meta->faviconPng) || count($meta->faviconPng) === 0)
            ) {
                $meta->faviconPng = [
                    ['href' => $faviconBase . '/favicon-96x96.png', 'sizes' => '96x96'],
                ];
            }

            if (property_exists($meta, 'faviconIco')
                && (empty($meta->faviconIco) || trim((string)$meta->faviconIco) === '')
            ) {
                $meta->faviconIco = $faviconBase . '/favicon.ico';
            }

            if (property_exists($meta, 'appleTouchIcons')
                && (empty($meta->appleTouchIcons) || count($meta->appleTouchIcons) === 0)
            ) {
                $meta->appleTouchIcons = [
                    ['href' => $faviconBase . '/apple-touch-icon.png', 'sizes' => '180x180'],
                ];
            }

            return $meta;
        }

        /**
         * Resolve a stored description without generating it from content.
         *
         * Read order:
         * 1) options['description']
         * 2) FrontendLinks.metadata_description
         * 3) Metadata.description
         *
         * @param object $frontendLink
         * @param object|null $metadata
         * @param array $options
         *
         * @return string
         */
        private static function resolveStoredDescription(object $frontendLink, ?object $metadata, array $options): string
        {
            $descriptionOverride = $options['description'] ?? null;
            if (is_string($descriptionOverride) && trim($descriptionOverride) !== '') {
                return trim($descriptionOverride);
            }

            foreach (['getMetadataDescription', 'MetadataDescription', 'metadata_description'] as $key) {
                if (method_exists($frontendLink, $key)) {
                    $val = $frontendLink->$key();
                    if (is_string($val) && trim($val) !== '') {
                        return trim($val);
                    }
                }

                if (property_exists($frontendLink, $key)) {
                    $val = $frontendLink->$key;
                    if (is_string($val) && trim($val) !== '') {
                        return trim($val);
                    }
                }
            }

            if ($metadata && method_exists($metadata, 'getDescription')) {
                $val = $metadata->getDescription();
                if (is_string($val) && trim($val) !== '') {
                    return trim($val);
                }
            }

            return '';
        }

        /**
         * Convert a root-relative or relative path to an absolute URL using the current request host.
         * If the URL is already absolute, it is returned unchanged.
         *
         * @param string $url
         * @return string
         */
        private static function toAbsoluteUrl(string $url): string
        {
            $url = trim($url);
            if ($url === '') {
                return '';
            }

            if (preg_match('~^https?://~i', $url)) {
                return $url;
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if ($host === '') {
                return $url;
            }

            return $scheme . '://' . $host . '/' . ltrim($url, '/');
        }

        /**
         * Build a robots meta string.
         *
         * Examples:
         * - index,follow
         * - noindex,nofollow
         *
         * @param bool $index
         * @param bool $follow
         * @return string
         */
        public static function buildRobots(bool $index = true, bool $follow = true): string
        {
            return ($index ? 'index' : 'noindex') . ',' . ($follow ? 'follow' : 'nofollow');
        }

        /**
         * Normalize a Twitter/X handle.
         *
         * Accepts either "name" or "@name" and always returns "@name".
         *
         * @param string $handle
         * @return string
         */
        public static function normalizeTwitterHandle(string $handle): string
        {
            $handle = trim($handle);
            if ($handle === '') {
                return '';
            }

            if ($handle[0] !== '@') {
                $handle = '@' . $handle;
            }

            return $handle;
        }
    }