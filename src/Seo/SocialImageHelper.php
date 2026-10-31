<?php

    namespace QCubed\Seo;

    use ContentCoverMedia;
    use DOMDocument;
    use DOMElement;
    use Files;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;

    /**
     * Helper for producing absolute OG/Twitter image URLs.
     *
     * Supports:
     *  - content cover media image flow
     *  - video preview_file_path flow
     *  - direct file path flow
     *  - generic collection flow
     *  - embedded media thumbnail fallback:
     *      - HTML5 <video poster="">
     *      - YouTube
     *      - Vimeo basic ID detection
     *
     * Media handling rules:
     * - media_type_id = 1 => picture_id => Files::load(picture_id) => getPath()
     * - media_type_id = 2 => preview_file_path from ContentCoverMedia
     * - media_type_id = 3 => preview_file_path first, then iframe fallback
     */
    final class SocialImageHelper
    {
        /**
         * Build origin (scheme + host) from current request.
         * Example: https://example.ee
         */
        public static function originFromRequest(): string
        {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $scheme = $https ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';

            return $host !== '' ? ($scheme . '://' . $host) : '';
        }

        /**
         * Resolve an absolute OG/Twitter image URL from a content object.
         *
         * @param object $content e.g. News / Article / Event / Sport
         * @param string $origin scheme+host, e.g. https://example.ee
         * @param string $size one of: thumbnail, medium, large
         *
         * @throws Caller
         * @throws InvalidCast
         */
        public static function resolveFromContent(object $content, string $origin, string $size = 'large'): ?string
        {
            $mediaTypeId = self::readInt($content, ['getMediaTypeId', 'media_type_id', 'MediaTypeId']);
            $coverMediaId = self::readInt($content, ['getContentCoverMediaId', 'content_cover_media_id', 'ContentCoverMediaId']);

            if (!$mediaTypeId || !$coverMediaId) {
                return null;
            }

            return self::resolveFromIds($mediaTypeId, $coverMediaId, $origin, $size);
        }

        /**
         * A backwards-compatible helper for video-embed-based content cover media.
         */
        public static function resolveFromContentCoverMediaEmbed(
            int $mediaTypeId,
            int $contentCoverMediaId,
            string $origin = ''
        ): ?string {
            if ($mediaTypeId !== 3 || $contentCoverMediaId <= 0) {
                return null;
            }

            return self::resolveFromIds($mediaTypeId, $contentCoverMediaId, $origin, 'large');
        }

        /**
         * Resolve poster/thumbnail URL from embedded media HTML.
         */
        public static function resolveFromEmbeddedMedia(string $html, string $origin = ''): ?string
        {
            $html = trim($html);
            if ($html === '') {
                return null;
            }

            return self::resolveVideoPosterFromHtml($html, $origin)
                ?? self::resolveYoutubeThumbnailFromHtml($html)
                ?? self::resolveVimeoThumbnailFromHtml($html);
        }

        /**
         * Resolve the poster directly from HTML5 <video poster="">.
         */
        public static function resolveVideoPosterFromHtml(string $html, string $origin = ''): ?string
        {
            $dom = self::createDomDocument($html);
            if (!$dom) {
                return null;
            }

            foreach ($dom->getElementsByTagName('video') as $video) {
                if (!$video instanceof DOMElement) {
                    continue;
                }

                $poster = trim($video->getAttribute('poster'));
                if ($poster === '') {
                    continue;
                }

                $poster = html_entity_decode($poster, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return self::toAbsoluteUrl($poster, $origin);
            }

            return null;
        }

        /**
         * Resolve YouTube thumbnail from an iframe/embed HTML.
         */
        public static function resolveYoutubeThumbnailFromHtml(string $html): ?string
        {
            foreach (self::extractMediaUrlsFromHtml($html) as $url) {
                $videoId = self::extractYoutubeVideoId($url);

                if ($videoId !== null) {
                    return 'https://img.youtube.com/vi/' . rawurlencode($videoId) . '/maxresdefault.jpg';
                }
            }

            return null;
        }

        /**
         * Resolve Vimeo thumbnail from an iframe/embed HTML.
         *
         * Vimeo thumbnail URLs are not safely predictable without oEmbed/API.
         * This method returns a practical no-API fallback via vumbnail.com.
         */
        public static function resolveVimeoThumbnailFromHtml(string $html): ?string
        {
            foreach (self::extractMediaUrlsFromHtml($html) as $url) {
                $videoId = self::extractVimeoVideoId($url);

                if ($videoId !== null) {
                    return 'https://vumbnail.com/' . rawurlencode($videoId) . '.jpg';
                }
            }

            return null;
        }

        /**
         * Resolves a media URL or path based on given identifiers.
         *
         * @throws Caller
         * @throws InvalidCast
         */
        public static function resolveFromIds(int $mediaTypeId, int $contentCoverMediaId, string $origin, string $size = 'large'): ?string
        {
            if ($mediaTypeId !== 1 && $mediaTypeId !== 2 && $mediaTypeId !== 3) {
                return null;
            }

            if (!class_exists('ContentCoverMedia') || !method_exists('ContentCoverMedia', 'load')) {
                return null;
            }

            $cover = ContentCoverMedia::load($contentCoverMediaId);
            if (!$cover) {
                return null;
            }

            /*
             * media_type_id = 1 => uploaded image
             */
            if ($mediaTypeId === 1) {
                $pictureId = self::readInt($cover, ['getPictureId', 'picture_id', 'PictureId']);
                if (!$pictureId || !class_exists('Files') || !method_exists('Files', 'load')) {
                    return null;
                }

                $file = Files::load($pictureId);
                if (!$file || !method_exists($file, 'getPath')) {
                    return null;
                }

                return self::resolveFromPath((string)$file->getPath(), $origin, $size);
            }

            /*
             * media_type_id = 2 or 3 => preview_file_path.
             *
             * For video embeds this may contain a full external poster URL,
             * for example, https://videoteek.ead.ee/thumb/...
             */
            $previewPath = self::readPreviewFilePath($cover);

            if ($previewPath !== '') {
                if (preg_match('~^https?://~i', $previewPath) || str_starts_with($previewPath, '//')) {
                    return self::toAbsoluteUrl($previewPath, $origin);
                }

                return self::resolveFromPath($previewPath, $origin, $size);
            }

            /*
             * media_type_id = 3 fallback:
             * If preview_file_path is empty, try resolving from an iframe itself.
             * This supports YouTube/Vimeo iframe thumbnails.
             */
            if ($mediaTypeId === 3) {
                $embedHtml = self::readEmbedString($cover);

                if ($embedHtml !== '') {
                    return self::resolveFromEmbeddedMedia($embedHtml, $origin);
                }
            }

            return null;
        }

        /**
         * Resolves a file path to an absolute URL based on the given origin and size.
         */
        public static function resolveFromPath(?string $path, string $origin, string $size = 'large'): ?string
        {
            $path = trim((string)$path);
            if ($path === '') {
                return null;
            }

            $combined = self::buildUploadsSizedPath($path, $size);

            return self::toAbsoluteUrl($combined, $origin);
        }

        /**
         * Resolves the first valid result from a list of paths.
         */
        public static function resolveFromPaths(array $paths, string $origin, string $size = 'large'): ?string
        {
            foreach ($paths as $path) {
                $resolved = self::resolveFromPath((string)$path, $origin, $size);
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            return null;
        }

        /**
         * A generic resolver for arrays/objects.
         */
        public static function resolveFromCollection(
            array $items,
            string $origin,
            string $size = 'large',
            array $statusCandidates = ['getStatus', 'status', 'Status'],
            array $pathCandidates = ['getPath', 'path', 'Path'],
            ?callable $filter = null
        ): ?string {
            foreach ($items as $item) {
                if ($filter !== null) {
                    if (!$filter($item)) {
                        continue;
                    }
                } else {
                    $status = self::mixedReadInt($item, $statusCandidates);
                    if ($status !== 1) {
                        continue;
                    }
                }

                $path = self::mixedReadString($item, $pathCandidates);
                if ($path === '') {
                    continue;
                }

                return self::resolveFromPath($path, $origin, $size);
            }

            return null;
        }

        /**
         * Converts a relative URL to an absolute URL using a specified origin.
         */
        public static function toAbsoluteUrl(string $url, string $origin): string
        {
            $url = trim($url);
            if ($url === '') {
                return '';
            }

            if (preg_match('~^https?://~i', $url)) {
                return $url;
            }

            if (str_starts_with($url, '//')) {
                return 'https:' . $url;
            }

            $origin = rtrim(trim($origin), '/');
            if ($origin === '') {
                return $url;
            }

            return $origin . '/' . ltrim($url, '/');
        }

        /**
         * Builds a sized path for uploads.
         */
        public static function buildUploadsSizedPath(string $path, string $size = 'large'): string
        {
            $path = trim($path);
            $size = in_array($size, ['thumbnail', 'medium', 'large'], true) ? $size : 'large';

            $base = rtrim(APP_UPLOADS_TEMP_URL, '/');
            $base .= '/_files/' . $size;

            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }

        /**
         * Extract iframe/video/source/embed URLs from HTML.
         *
         * @return array<int, string>
         */
        private static function extractMediaUrlsFromHtml(string $html): array
        {
            $dom = self::createDomDocument($html);
            if (!$dom) {
                return [];
            }

            $urls = [];

            foreach (['iframe', 'video', 'source', 'embed'] as $tagName) {
                foreach ($dom->getElementsByTagName($tagName) as $node) {
                    if (!$node instanceof DOMElement) {
                        continue;
                    }

                    foreach (['src', 'data-src'] as $attribute) {
                        $url = trim($node->getAttribute($attribute));
                        if ($url !== '') {
                            $urls[] = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        }
                    }
                }
            }

            return array_values(array_unique($urls));
        }

        /**
         * Extract YouTube video ID from common YouTube URL formats.
         */
        private static function extractYoutubeVideoId(string $url): ?string
        {
            $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($url === '') {
                return null;
            }

            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }

            $host = strtolower((string)parse_url($url, PHP_URL_HOST));
            $path = (string)parse_url($url, PHP_URL_PATH);
            $query = (string)parse_url($url, PHP_URL_QUERY);

            if ($host === '') {
                return null;
            }

            if (str_contains($host, 'youtube.com')) {
                parse_str($query, $params);

                if (!empty($params['v']) && is_string($params['v'])) {
                    return self::cleanVideoId($params['v']);
                }

                if (preg_match('~/embed/([A-Za-z0-9_-]{6,})~', $path, $matches)) {
                    return self::cleanVideoId($matches[1]);
                }

                if (preg_match('~/shorts/([A-Za-z0-9_-]{6,})~', $path, $matches)) {
                    return self::cleanVideoId($matches[1]);
                }
            }

            if (str_contains($host, 'youtu.be')) {
                $id = trim($path, '/');
                if ($id !== '') {
                    return self::cleanVideoId($id);
                }
            }

            return null;
        }

        /**
         * Extract Vimeo video ID from common Vimeo URL formats.
         */
        private static function extractVimeoVideoId(string $url): ?string
        {
            $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($url === '') {
                return null;
            }

            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }

            $host = strtolower((string)parse_url($url, PHP_URL_HOST));
            $path = (string)parse_url($url, PHP_URL_PATH);

            if ($host === '' || !str_contains($host, 'vimeo.com')) {
                return null;
            }

            if (preg_match('~/(?:video/)?([0-9]{5,})~', $path, $matches)) {
                return $matches[1];
            }

            return null;
        }

        /**
         * Clean external video ID.
         */
        private static function cleanVideoId(string $id): ?string
        {
            $id = trim($id);

            if ($id === '') {
                return null;
            }

            if (!preg_match('~^[A-Za-z0-9_-]+$~', $id)) {
                return null;
            }

            return $id;
        }

        /**
         * Create DOMDocument safely from HTML fragment.
         */
        private static function createDomDocument(string $html): ?DOMDocument
        {
            $html = trim($html);
            if ($html === '') {
                return null;
            }

            $previous = libxml_use_internal_errors(true);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $loaded = $dom->loadHTML(
                '<?xml encoding="UTF-8">' . $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $loaded ? $dom : null;
        }

        /**
         * Reads an integer value from the given object by checking specified method or property names.
         */
        private static function readInt(object $obj, array $candidates): int
        {
            foreach ($candidates as $key) {
                if (method_exists($obj, $key)) {
                    $val = $obj->$key();
                    if (is_numeric($val)) {
                        return (int)$val;
                    }
                }

                if (property_exists($obj, $key)) {
                    $val = $obj->$key;
                    if (is_numeric($val)) {
                        return (int)$val;
                    }
                }
            }

            return 0;
        }

        /**
         * Reads preview_file_path from ContentCoverMedia.
         */
        private static function readPreviewFilePath(object $obj): string
        {
            return self::readStringByCandidates($obj, [
                'getPreviewFilePath',
                'preview_file_path',
                'PreviewFilePath',
            ]);
        }

        /**
         * Reads embed HTML from ContentCoverMedia.
         */
        private static function readEmbedString(object $obj): string
        {
            return self::readStringByCandidates($obj, [
                'getVideoEmbed',
                'video_embed',
                'VideoEmbed',
                'getEmbedCode',
                'getMediaEmbed',
                'getContentEmbed',
                'getIframeCode',
                'getCode',
                'embed_code',
                'media_embed',
                'content_embed',
                'iframe_code',
                'code',
                'EmbedCode',
                'MediaEmbed',
                'ContentEmbed',
                'IframeCode',
                'Code',
            ]);
        }

        /**
         * Reads a string value from an object by checking candidate methods/properties.
         */
        private static function readStringByCandidates(object $obj, array $candidates): string
        {
            foreach ($candidates as $key) {
                if (method_exists($obj, $key)) {
                    $val = $obj->$key();
                    if (is_string($val) || is_numeric($val)) {
                        return (string)$val;
                    }
                }

                if (property_exists($obj, $key)) {
                    $val = $obj->$key;
                    if (is_string($val) || is_numeric($val)) {
                        return (string)$val;
                    }
                }
            }

            return '';
        }

        /**
         * Reads an integer from an object or array using specified candidates.
         */
        private static function mixedReadInt(mixed $item, array $candidates): int
        {
            if (is_object($item)) {
                foreach ($candidates as $key) {
                    if (method_exists($item, $key)) {
                        $val = $item->$key();
                        if (is_numeric($val)) {
                            return (int)$val;
                        }
                    }

                    if (property_exists($item, $key)) {
                        $val = $item->$key;
                        if (is_numeric($val)) {
                            return (int)$val;
                        }
                    }
                }
            }

            if (is_array($item)) {
                foreach ($candidates as $key) {
                    if (array_key_exists($key, $item) && is_numeric($item[$key])) {
                        return (int)$item[$key];
                    }
                }
            }

            return 0;
        }

        /**
         * Extracts a string or numeric value from the given item.
         */
        private static function mixedReadString(mixed $item, array $candidates): string
        {
            if (is_object($item)) {
                foreach ($candidates as $key) {
                    if (method_exists($item, $key)) {
                        $val = $item->$key();
                        if (is_string($val) || is_numeric($val)) {
                            return (string)$val;
                        }
                    }

                    if (property_exists($item, $key)) {
                        $val = $item->$key;
                        if (is_string($val) || is_numeric($val)) {
                            return (string)$val;
                        }
                    }
                }
            }

            if (is_array($item)) {
                foreach ($candidates as $key) {
                    if (array_key_exists($key, $item) && (is_string($item[$key]) || is_numeric($item[$key]))) {
                        return (string)$item[$key];
                    }
                }
            }

            return '';
        }
    }