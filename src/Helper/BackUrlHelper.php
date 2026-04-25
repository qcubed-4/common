<?php
    declare(strict_types=1);

    namespace QCubed\Helper;

    use QCubed\Project\Application;

    /**
     * Class BackUrlHelper
     *
     * Responsible for the frontend "Back" navigation logic.
     *
     * Problem we solve:
     * --------------------------
     * Browser history.back() is not always reliable,
     * because:
     *
     * - JS plugins (e.g. NanoGallery) can modify the history stack
     * - SPA components can perform pushState manipulations
     * - the user can come to the detail view from several different places
     *
     * Therefore, before navigating, we store:
     *
     * previousUrl = page the user leaves
     * targetUrl = page the user is moving to
     *
     * These values are sent to the server via JS and
     * stored in the session.
     *
     * When the Back button is pressed:
     *
     * 1) we check if the session has a previousUrl
     * 2) we check if the targetUrl corresponds to the current page
     * 3) if yes -> redirect previousUrl
     * 4) if no -> use the fallback URL
     *
     * This provides much more reliable navigation than browser history.
     */
    final class BackUrlHelper
    {

        /**
         * Session key: previous URL
         */
        private const SESSION_PREVIOUS_URL = 'back_navigation.previous_url';

        /**
         * Session key: destination URL
         */
        private const SESSION_TARGET_URL = 'back_navigation.target_url';

        /**
         * Set to TRUE if you want a navigation debug log.
         */
        private const DEBUG = false;

        /**
         * Saves the navigation state to the session.
         *
         * This is called from the AJAX endpoint,
         * where main.js sends the previousUrl and targetUrl.
         *
         * @param string $previousUrl
         * @param string $targetUrl
         */
        public static function setNavigation(string $previousUrl, string $targetUrl): void
        {
            self::ensureSessionStarted();

            $previousUrl = self::sanitizeInternalUrl($previousUrl);
            $targetUrl   = self::sanitizeInternalUrl($targetUrl);

            if ($previousUrl !== null) {
                $_SESSION[self::SESSION_PREVIOUS_URL] = $previousUrl;
            }

            if ($targetUrl !== null) {
                $_SESSION[self::SESSION_TARGET_URL] = $targetUrl;
            }
        }

        /**
         * Returns the previousUrl value stored in the session.
         *
         * @return string|null
         */
        public static function getPreviousUrl(): ?string
        {
            self::ensureSessionStarted();

            $url = $_SESSION[self::SESSION_PREVIOUS_URL] ?? null;

            if (!is_string($url)) {
                return null;
            }

            return self::sanitizeInternalUrl($url);
        }

        /**
         * Returns the targetUrl value stored in the session.
         *
         * @return string|null
         */
        public static function getTargetUrl(): ?string
        {
            self::ensureSessionStarted();

            $url = $_SESSION[self::SESSION_TARGET_URL] ?? null;

            if (!is_string($url)) {
                return null;
            }

            return self::sanitizeInternalUrl($url);
        }

        /**
         * Clears all back-navigation states from the session.
         *
         * It makes sense to call this, for example:
         *
         * - when the user clicks on the logo (homepage)
         * - logout
         * - language change
         * - new navigation starts
         */
        public static function clear(): void
        {
            self::ensureSessionStarted();

            unset(
                $_SESSION[self::SESSION_PREVIOUS_URL],
                $_SESSION[self::SESSION_TARGET_URL]
            );
        }

        /**
         * The main method for the Back button.
         *
         * Attempts to redirect the user back to the previous page.
         * If this fails, the fallback URL is used.
         *
         * @param string $fallbackUrl
         *
         * @throws \Throwable
         */
        public static function goBack(string $fallbackUrl = '/'): void
        {
            $fallbackUrl = self::sanitizeInternalUrl($fallbackUrl) ?? '/';

            $currentUrl  = self::currentRequestUrl();
            $previousUrl = self::getPreviousUrl();
            $targetUrl   = self::getTargetUrl();

            self::debugLog($previousUrl, $targetUrl, $currentUrl);

            /**
             * Check if the previousUrl is valid and different
             * from the current page URL.
             */
            if (
                $previousUrl !== null &&
                $previousUrl !== $currentUrl &&
                (
                    /**
                     * If targetUrl is missing or
                     * targetUrl matches the current page,
                     * then redirect to the previousUrl.
                     */
                    $targetUrl === null ||
                    self::normalizeUrl($targetUrl) === self::normalizeUrl($currentUrl)
                )
            ) {
                self::clear();
                Application::redirect($previousUrl);
                return;
            }

            /**
             * If the previousUrl doesn't match, we use the fallback URL.
             */
            self::clear();
            Application::redirect($fallbackUrl);
        }

        /**
         * Returns the current request URL.
         *
         * @return string
         */
        public static function currentRequestUrl(): string
        {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            return self::sanitizeInternalUrl($requestUri) ?? '/';
        }

        /**
         * Ensures that the session is started.
         */
        private static function ensureSessionStarted(): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
        }

        /**
         * Checks and cleans the internal URL.
         *
         * We only allow relative path type URLs:
         *
         * OK:
         * /news
         * /news?page=2
         *
         * NOT ALLOWED:
         * http://...
         * https://...
         * //example.com
         *
         * @param string $url
         * @return string|null
         */
        private static function sanitizeInternalUrl(string $url): ?string
        {
            $url = trim($url);

            if ($url === '') {
                return null;
            }

            if (!str_starts_with($url, '/')) {
                return null;
            }

            if (str_starts_with($url, '//')) {
                return null;
            }

            return $url;
        }

        /**
         * Normalizes the URL for comparison.
         *
         * Removes trailing slashes, etc.
         *
         * @param string $url
         * @return string
         */
        private static function normalizeUrl(string $url): string
        {
            $parts = parse_url($url);

            if ($parts === false) {
                return $url;
            }

            $path = $parts['path'] ?? '/';
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

            if ($path !== '/') {
                $path = rtrim($path, '/');
                if ($path === '') {
                    $path = '/';
                }
            }

            return $path . $query . $fragment;
        }

        /**
         * Debug log to check navigation.
         *
         * Logs previousUrl, targetUrl and currentUrl.
         */
        private static function debugLog(?string $previousUrl, ?string $targetUrl, string $currentUrl): void
        {
            if (!self::DEBUG) {
                return;
            }

            $message = sprintf(
                'BackNavigation | previous: %s | target: %s | current: %s',
                $previousUrl ?? 'NULL',
                $targetUrl ?? 'NULL',
                $currentUrl
            );

            error_log($message);
        }
    }