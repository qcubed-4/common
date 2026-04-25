<?php

    namespace QCubed\Seo;

    use ContentCoverMedia;
    use Files;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;

    /**
     * Helper for producing absolute OG/Twitter image URLs.
     *
     * Supports:
     *  - existing content cover media flow
     *  - direct file path flow
     *  - generic collection flow (arrays/objects), e.g. gallery items, albums, custom items
     *
     * Works with your content objects that expose:
     * - getMediaTypeId() / media_type_id
     * - getContentCoverMediaId() / content_cover_media_id
     *
     * Media handling rules:
     * - media_type_id = 1 => picture_id => Files::load(picture_id) => getPath()
     * - media_type_id = 2 => preview_file_path from ContentCoverMedia
     * - media_type_id = 3 => video embed (skip)
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
         * Resolves a media URL or path based on given identifiers and parameters.
         *
         * @param int $mediaTypeId The type ID of the media (e.g., 1 for images, 2 for other types).
         * @param int $contentCoverMediaId The ID of the content cover media.
         * @param string $origin The origin from which the media is being requested.
         * @param string $size The size parameter for the media, defaults to 'large'.
         *
         * @return string|null The resolved media URL or path, or null if resolution fails.
         * @throws Caller
         * @throws InvalidCast
         */
        public static function resolveFromIds(int $mediaTypeId, int $contentCoverMediaId, string $origin, string $size = 'large'): ?string
        {
            if ($mediaTypeId !== 1 && $mediaTypeId !== 2) {
                return null;
            }

            if (!class_exists('ContentCoverMedia') || !method_exists('ContentCoverMedia', 'load')) {
                return null;
            }

            $cover = ContentCoverMedia::load($contentCoverMediaId);
            if (!$cover) {
                return null;
            }

            $path = '';

            if ($mediaTypeId === 1) {
                $pictureId = self::readInt($cover, ['getPictureId', 'picture_id', 'PictureId']);
                if (!$pictureId || !class_exists('Files') || !method_exists('Files', 'load')) {
                    return null;
                }

                $file = Files::load($pictureId);
                if (!$file || !method_exists($file, 'getPath')) {
                    return null;
                }

                $path = (string)$file->getPath();
            }

            if ($mediaTypeId === 2) {
                $path = self::readString($cover);
            }

            return self::resolveFromPath($path, $origin, $size);
        }

        /**
         * Resolves a file path to an absolute URL based on the given origin and size.
         *
         * Example input path:
         * V/pildigalerii/foo/bar.jpg
         *
         * @param string|null $path The relative file path to resolve. If null or empty, the method returns null.
         * @param string $origin The base URL or origin to use for constructing the absolute URL.
         * @param string $size The size of the resource, defaulting to 'large'.
         *
         * @return string|null The absolute URL for the provided path or null if the path is empty.
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
         * Resolves a valid result from a list of paths based on the specified origin and size.
         *
         * @param array $paths An array of paths to search through for resolution.
         * @param string $origin The origin parameter to use in the resolution process.
         * @param string $size The size parameter to use in the resolution process. Defaults to 'large'.
         *
         * @return string|null The resolved result if found, or null if no result is resolved.
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
         *
         * Example use-case:
         *  - gallery image list
         *  - album rows
         *  - custom collections in future
         *
         * By default,
         *  - status must equal 1
         *  - path is read from getPath()/path/Path
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
         *
         * This method takes a URL and determines whether it is already an absolute URL
         * (starting with http or https). If it is, the URL is returned as-is. If it is
         * a relative URL, the method uses the provided origin to construct an absolute URL.
         * Trailing slashes from the origin and leading slashes from the URL are managed
         * to ensure proper concatenation.
         *
         * If the URL is empty or the origin is empty, the method returns the URL as-is.
         *
         * @param string $url The URL to be converted. It can be an absolute or relative URL.
         * @param string $origin The base URL (origin) used to convert relative URLs to absolute URLs.
         *
         * @return string The absolute URL if the input is relative, or the original URL if it is already absolute.
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

            $origin = rtrim(trim($origin), '/');
            if ($origin === '') {
                return $url;
            }

            return $origin . '/' . ltrim($url, '/');
        }

        /**
         * Builds a sized path for uploads by appending the given size to the base upload URL.
         *
         * @param string $path The relative file path to be used in the generated URL.
         * @param string $size The size of the upload, expected to be one of 'thumbnail', 'medium', or 'large'. Defaults to 'large'.
         *
         * @return string The constructed upload path including the specified size.
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
         * Reads an integer value from the given object by checking specified method or property names.
         *
         * This method iterates through the provided candidate names and checks for their existence
         * as either methods or properties within the given object. If a method exists and returns
         * a numeric value, it is cast to an integer and returned. Similarly, if a property exists
         * with a numeric value, it is cast to an integer and returned. If no valid methods or properties
         * are found, the method returns 0 by default.
         *
         * @param object $obj The object to read the integer value from.
         * @param array $candidates An array of method or property names to check within the object.
         *
         * @return int The extracted integer value or 0 if no matching methods or properties are found.
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
         * Reads a string value from the given object by checking defined method or property names.
         *
         * This method checks for the existence of certain method or property names
         * within the provided object. If a method exists and its return value is a string
         * or numeric, it is returned as a string. Similarly, if a property exists with
         * a string or numeric value, it is returned as a string. If none of the specified
         * methods or properties exist or are valid, an empty string is returned.
         *
         * @param object $obj The object to read the string value from.
         *
         * @return string The extracted string value or an empty string if no matching methods or properties are found.
         */
        private static function readString(object $obj): string
        {
            $candidates = ['getPreviewFilePath', 'preview_file_path', 'PreviewFilePath'];

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
         *
         * @param mixed $item The object or array to read the integer value from.
         * @param array $candidates A list of candidate keys or methods to check in the object or array.
         *
         * @return int The integer value found based on the specified candidates, or 0 if no valid integer is found.
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
         * Extracts a string or numeric value from the given item, based on a list of candidate keys or methods.
         *
         * If the item is an object, the method checks if the candidates are either existing properties or methods
         * and retrieves their value if it's a string or numeric. If the item is an array, the method checks
         * if the candidates exist as keys and retrieves their value if it's a string or numeric.
         *
         * @param mixed $item The input item, which can be an object or an array.
         * @param array $candidates A list of keys or method names to attempt retrieval from the item.
         *
         * @return string The extracted string or numeric value, returned as a string, or an empty string if no match is found.
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