<?php
    namespace QCubed\Seo;

    /**
     * Generate page metadata based on the provided objects and options.
     *
     * @param object $frontendLink An object representing the link data.
     * @param object|null $metadata An optional object containing predefined metadata.
     * @param object|null $content An optional object containing content information.
     * @param array $options Configuration options to customize metadata generation. Supported options:
     *   - title (string|null) Custom title to override defaults.
     *   - lead (string|null) Custom lead text, fallback if available.
     *   - contentText (string|null) Custom content text, fallback if available.
     *   - prefix (string|null) Prefix used in description generation.
     *   - separator (string|null) Separator used in description generation.
     *   - min (int|null) Minimum character length for description.
     *   - target (int|null) Target character length for description.
     *   - max (int|null) Maximum character length for description.
     *   - imageUrl (string|null) Explicit image URL for og:image.
     *   - imageResolver (callable|null) Callback to resolve image URL dynamically.
     *   - sentenceEndRegex (string|null) Regex for detecting sentence ends.
     *
     * @return PageMeta Returns a populated PageMeta object.
     */
    final class PageMetaHelper_Backup
    {
        /**
         * A basic builder (as before)
         */
        public static function build(
            object $frontendLink,
            ?object $metadata = null,
            ?object $content = null,
            array $options = []
        ): PageMeta {
            $meta = new PageMeta();

            // --- TITLE ---
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

            // --- MANUAL DESCRIPTION (Metadata.description) ---
            $manualDescription = null;
            if ($metadata && method_exists($metadata, 'getDescription')) {
                $manualDescription = $metadata->getDescription();
            }

            // --- Lead / Content text ---
            $lead = $options['lead'] ?? null;
            $contentText = $options['contentText'] ?? null;

            if ($content) {
                if ($lead === null && method_exists($content, 'getLead')) {
                    $lead = $content->getLead();
                }
                if ($contentText === null && method_exists($content, 'getContent')) {
                    $contentText = $content->getContent();
                }
            }

            $prefix = $options['prefix'] ?? null;
            $separator = $options['separator'] ?? null;

            $min = $options['min'] ?? null;
            $target = $options['target'] ?? null;
            $max = $options['max'] ?? null;

            $sentenceEndRegexOverride = $options['sentenceEndRegex'] ?? null;

            $meta->description = MetadataResolver::resolveDescription(
                $manualDescription,
                is_string($lead) ? $lead : null,
                is_string($contentText) ? $contentText : null,
                $meta->title,
                is_string($prefix) ? $prefix : null,
                is_string($separator) ? $separator : null,
                is_int($min) ? $min : null,
                is_int($target) ? $target : null,
                is_int($max) ? $max : null,
                is_string($sentenceEndRegexOverride) ? $sentenceEndRegexOverride : null
            );

            // --- OG defaults ---
            $meta->ogTitle = $meta->title;
            $meta->ogDescription = $meta->description;

            // --- IMAGE ---
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


            // If og:image is still empty, allow a global fallback image (e.g. site-wide default OG image).
            $defaultImage = $options['defaultImage'] ?? null;
            if ((empty($meta->ogImage) || trim((string)$meta->ogImage) === '') && is_string($defaultImage) && trim($defaultImage) !== '') {
                $meta->ogImage = self::toAbsoluteUrl(trim($defaultImage));
            }

            // Keep twitter image in sync with og:image
            if (!empty($meta->ogImage)) {
                $meta->twitterImage = $meta->ogImage;
            }

            // --- Twitter defaults ---
            if (!$meta->ogImage) {
                $meta->twitterCard = 'summary';
            }

            $meta->twitterTitle = $meta->ogTitle;
            $meta->twitterDescription = $meta->ogDescription;
            $meta->twitterImage = $meta->ogImage;

            return $meta;
        }

        /**
         * FULL builder:
         * - includes canonical, robots, og:url, og:type, og:site_name, twitter:site/creator
         *
         * @param string|null $absoluteUrl Example: https://site.ee/uudis/minu-uudis
         * @param string|null $siteName Example: "My Sports Portal"
         * @param string|null $twitterSite Example: "@portal" (optional)
         * @param array $options same as build() + extras:
         *   - ogType (string|null) override og:type (e.g. "article")
         *   - isDetail (bool|null) force list/detail logic
         *   - robotsIndex (bool|null) default true
         *   - robotsFollow (bool|null) default true
         *   - twitterCreator (string|null) optional "@author"
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

            $meta->robots = self::buildRobots(
                (bool)$robotsIndex,
                (bool)$robotsFollow
            );

            // og:type
            $ogTypeOverride = $options['ogType'] ?? null;

            if (is_string($ogTypeOverride) && trim($ogTypeOverride) !== '') {
                $meta->ogType = trim($ogTypeOverride);
            } else {
                // auto detect list/detail
                $isDetail = $options['isDetail'] ?? null;

                if ($isDetail === null) {
                    // If there is a content object => likely detail page
                    $isDetail = ($content !== null);
                }

                $meta->ogType = $isDetail ? 'article' : 'website';
            }

            // twitter:site (optional)
            $twitterSite = is_string($twitterSite) ? trim($twitterSite) : null;
            if (!empty($twitterSite)) {
                $meta->twitterSite = self::normalizeTwitterHandle($twitterSite);
            }

            // twitter:creator (optional)
            $twitterCreator = $options['twitterCreator'] ?? null;
            if (is_string($twitterCreator) && trim($twitterCreator) !== '') {
                $meta->twitterCreator = self::normalizeTwitterHandle(trim($twitterCreator));
            }


            // --- Favicons / Manifest / Apple Touch Icons ---
            // Provide sensible defaults via options['faviconBasePath'] (or legacy aliases), so templates don't need to hardcode these.
            // Example: '/frontend/assets/favicon'
            $faviconBase = $options['faviconBasePath'] ?? ($options['iconBasePath'] ?? ($options['defaultFavicon'] ?? null));
            if (!is_string($faviconBase) || trim($faviconBase) === '') {
                $faviconBase = '/frontend/assets/favicon';
            }
            $faviconBase = rtrim($faviconBase, '/');

            // apple-mobile-web-app-title defaults to siteName when available
            if (property_exists($meta, 'appleMobileWebAppTitle') && (empty($meta->appleMobileWebAppTitle) || trim((string)$meta->appleMobileWebAppTitle) === '')) {
                if (!empty($siteName)) {
                    $meta->appleMobileWebAppTitle = $siteName;
                }
            }

            if (property_exists($meta, 'manifestUrl') && (empty($meta->manifestUrl) || trim((string)$meta->manifestUrl) === '')) {
                $meta->manifestUrl = $faviconBase . '/site.webmanifest';
            }

            if (property_exists($meta, 'faviconSvg') && (empty($meta->faviconSvg) || trim((string)$meta->faviconSvg) === '')) {
                $meta->faviconSvg = $faviconBase . '/favicon.svg';
            }

            // Default PNG favicon: RealFaviconGenerator often includes a 96x96 (or 32/16). Your current set has 96x96.
            if (property_exists($meta, 'faviconPng') && (empty($meta->faviconPng) || count($meta->faviconPng) === 0)) {
                $meta->faviconPng = [
                    ['href' => $faviconBase . '/favicon-96x96.png', 'sizes' => '96x96'],
                ];
            }

            if (property_exists($meta, 'faviconIco') && (empty($meta->faviconIco) || trim((string)$meta->faviconIco) === '')) {
                $meta->faviconIco = $faviconBase . '/favicon.ico';
            }

            if (property_exists($meta, 'appleTouchIcons') && (empty($meta->appleTouchIcons) || count($meta->appleTouchIcons) === 0)) {
                $meta->appleTouchIcons = [
                    ['href' => $faviconBase . '/apple-touch-icon.png', 'sizes' => '180x180'],
                ];
            }

            return $meta;
        }


        /**
         * Convert a URL/path to an absolute URL using the current request host.
         * - If already absolute (http/https), returns as-is.
         * - If root-relative (/path), prefixes with a scheme://host.
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
         * Build robots meta string.
         */
        public static function buildRobots(bool $index = true, bool $follow = true): string
        {
            return ($index ? 'index' : 'noindex') . ',' . ($follow ? 'follow' : 'nofollow');
        }

        /**
         * Normalize a Twitter handle:
         * - allow "@name" or "name"
         * - always return "@name"
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

    /** Examples of use cases on the frontend side */

    /**
     * DETAIL (NEWS) example
     *
     * $pageMeta = \QCubed\Seo\PageMetaHelper::buildFull(
     *      $frontendLink,
     *      $metadata,
     *      $news,
     *      $absoluteUrl,
     *      $siteName,
     *      $clientTwitterAccount, // zero if not
     *      [
     *          'prefix' => 'News',
     *          'separator' => ' | ',
     *          'isDetail' => true,
     *          'imageResolver' => function ($content) {
     *              if ($content && method_exists($content, 'getPictureId') && $content->getPictureId()) {
     *                  $file = \Files::load($content->getPictureId());
     *                  return APP_UPLOADS_TEMP_URL. '/_files/medium'. $file->getPath();
     *              }
     *              return null;
     *              }
     *      ]
     * );
     */

    /**
     * LIST (NEWS) example
     *
     *pageMeta = \QCubed\Seo\PageMetaHelper::buildFull(
     *      $frontendLink,
     *      $metadata,
     *      null,
     *      $absoluteUrl,
     *      $siteName,
     *      $clientTwitterAccount,
     *      [
     *      'prefix' => 'News',
     *      'separator' => ' | ',
     *      'isDetail' => false,
     *      ]
     * );
     */

    /**
     * <HEAD> example
     *
     *  \QCubed\Seo\MetaRenderer::render($pageMeta);
     *
     */