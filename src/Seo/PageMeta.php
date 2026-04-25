<?php

    namespace QCubed\Seo;

    /**
     * Value object for page-level SEO, Open Graph, Twitter Card
     * and app/favicon metadata.
     *
     * Responsibility:
     * - hold prepared metadata values for one page
     * - be consumed by MetaRenderer
     *
     * Non-responsibility:
     * - does not generate metadata
     * - does not read from a database
     * - does not resolve descriptions from content
     */
    final class PageMeta
    {
        /**
         * Standard HTML <title>.
         */
        public string $title = '';

        /**
         * Standard meta description.
         */
        public string $description = '';

        /**
         * Canonical URL of the page.
         */
        public ?string $canonicalUrl = null;

        /**
         * Robots directive, for example:
         * "index,follow" or "noindex,nofollow"
         */
        public ?string $robots = null;

        /**
         * Open Graph title.
         */
        public string $ogTitle = '';

        /**
         * Open Graph description.
         */
        public string $ogDescription = '';

        /**
         * Absolute OG image URL.
         */
        public ?string $ogImage = null;

        /**
         * Absolute OG page URL.
         */
        public ?string $ogUrl = null;

        /**
         * OG object type, usually "website" or "article".
         */
        public ?string $ogType = null;

        /**
         * Site name for OG cards.
         */
        public ?string $ogSiteName = null;

        /**
         * Twitter card type, for example,
         * "summary" or "summary_large_image"
         */
        public string $twitterCard = 'summary_large_image';

        /**
         * Twitter title.
         */
        public string $twitterTitle = '';

        /**
         * Twitter description.
         */
        public string $twitterDescription = '';

        /**
         * Absolute Twitter image URL.
         */
        public ?string $twitterImage = null;

        /**
         * Twitter/X site handle, e.g. "@myportal".
         */
        public ?string $twitterSite = null;

        /**
         * Twitter/X creator handle, e.g. "@author".
         */
        public ?string $twitterCreator = null;

        /**
         * App title used by Apple web app meta.
         */
        public ?string $appleMobileWebAppTitle = null;

        /**
         * Web app manifest URL.
         */
        public ?string $manifestUrl = null;

        /**
         * Preferred a modern favicon.
         * Example: /frontend/assets/favicon/favicon.svg
         */
        public ?string $faviconSvg = null;

        /**
         * PNG favicons.
         * Each item: ['href' => string, 'sizes' => '96x96', 'type' => 'image/png']
         *
         * @var array<int, array<string, string>>
         */
        public array $faviconPng = [];

        /**
         * Legacy favicon.
         * Example: /frontend/assets/favicon/favicon.ico
         */
        public ?string $faviconIco = null;

        /**
         * Apple touch icons.
         * Each item: ['href' => string, 'sizes' => '180x180']
         *
         * @var array<int, array<string, string>>
         */
        public array $appleTouchIcons = [];

        /**
         * Number of spaces used by MetaRenderer for pretty indentation.
         */
        public int $indentNumber = 4;

        /**
         * Optional browser/theme color.
         * Example: #ffffff
         */
        protected ?string $themeColor = null;

        /**
         * Export all metadata as an array.
         */
        public function toArray(): array
        {
            return [
                'title' => $this->title,
                'description' => $this->description,
                'canonical' => $this->canonicalUrl,
                'robots' => $this->robots,

                'apple-mobile-web-app-title' => $this->appleMobileWebAppTitle,
                'manifest' => $this->manifestUrl,
                'favicon:svg' => $this->faviconSvg,
                'favicon:png' => $this->faviconPng,
                'favicon:ico' => $this->faviconIco,
                'apple-touch-icons' => $this->appleTouchIcons,

                'og:title' => $this->ogTitle,
                'og:description' => $this->ogDescription,
                'og:image' => $this->ogImage,
                'og:url' => $this->ogUrl,
                'og:type' => $this->ogType,
                'og:site_name' => $this->ogSiteName,

                'twitter:card' => $this->twitterCard,
                'twitter:title' => $this->twitterTitle,
                'twitter:description' => $this->twitterDescription,
                'twitter:image' => $this->twitterImage,
                'twitter:site' => $this->twitterSite,
                'twitter:creator' => $this->twitterCreator,
            ];
        }

        /**
         * Set a theme color.
         */
        public function setThemeColor(?string $themeColor): self
        {
            $this->themeColor = $themeColor;
            return $this;
        }

        /**
         * Get theme color.
         */
        public function getThemeColor(): ?string
        {
            return $this->themeColor;
        }
    }