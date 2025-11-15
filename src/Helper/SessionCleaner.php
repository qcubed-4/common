<?php

    namespace QCubed\Helper;

    /**
     * Class SessionCleaner
     *
     * A safe utility to automatically remove temporary or expired session keys
     * while preserving important ones, such as authentication or security keys.
     *
     * Features:
     * - Automatic expiration cleanup (autoClean)
     * - Manual cleanup for specific temporary keys (clean)
     * - Preservation of important keys (setPreserveKeys)
     * - Timestamp-based expiration for each cleaned key
     * - Safe for QCubed-4 session handling
     * - URL debug mode (?sc_debug=1)
     * - Debug output via debugDump()
     */
    class SessionCleaner
    {
        /** @var array List of keys that must NEVER be removed */
        protected static array $preserveKeys = [];

        /** @var bool Prevent autoClean from running twice per request */
        protected static bool $hasRunAutoClean = false;

        /** @var string Timestamp prefix for each managed session key */
        protected const string TS_PREFIX = '__sc_ts_';

        /**
         * Define keys that should never be removed by the cleaner.
         * Call this once at your backend bootstrap/header.
         *
         * @param array $keys
         * @return void
         */
        public static function setPreserveKeys(array $keys): void
        {
            self::$preserveKeys = array_unique(array_merge(self::$preserveKeys, $keys));
        }

        /**
         * Check if a key is preserved.
         *
         * @param string $key
         * @return bool
         */
        protected static function isPreserved(string $key): bool
        {
            return in_array($key, self::$preserveKeys, true);
        }

        /**
         * Automatic global cleanup — removes ALL non-preserved keys
         * that are older than $maxAgeSeconds.
         *
         * Example:
         *     SessionCleaner::autoClean(1800);
         *
         * @param int $maxAgeSeconds
         * @return void
         */
        public static function autoClean(int $maxAgeSeconds): void
        {
            if (self::$hasRunAutoClean) {
                return;
            }

            self::$hasRunAutoClean = true;

            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }

            foreach ($_SESSION as $key => $value) {

                if (self::isPreserved($key)) {
                    continue;
                }

                $tsKey = self::TS_PREFIX . $key;

                if (!isset($_SESSION[$tsKey])) {
                    continue;
                }

                $age = time() - (int)$_SESSION[$tsKey];

                if ($age >= $maxAgeSeconds) {
                    unset($_SESSION[$key], $_SESSION[$tsKey]);
                }
            }

            // URL Debug Mode (?sc_debug=1)
            if (isset($_GET['sc_debug']) && $_GET['sc_debug'] === '1') {
                self::debugDump();
            }
        }

        /**
         * Clean only specific keys if they are older than $maxAgeSeconds.
         *
         * Example:
         *     SessionCleaner::clean(['user_id', 'temp_upload'], 1800);
         *
         * @param array $keys
         * @param int $maxAgeSeconds
         * @return void
         */
        public static function clean(array $keys, int $maxAgeSeconds): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }

            foreach ($keys as $key) {
                if (self::isPreserved($key)) {
                    continue;
                }

                $tsKey = self::TS_PREFIX . $key;

                if (isset($_SESSION[$key]) && !isset($_SESSION[$tsKey])) {
                    $_SESSION[$tsKey] = time();
                }

                if (!isset($_SESSION[$tsKey])) {
                    continue;
                }

                $age = time() - (int)$_SESSION[$tsKey];

                if ($age >= $maxAgeSeconds) {
                    unset($_SESSION[$key], $_SESSION[$tsKey]);
                }
            }

            // URL Debug Mode
            if (isset($_GET['sc_debug']) && $_GET['sc_debug'] === '1') {
                self::debugDump();
            }
        }

        /**
         * Print session contents for debugging.
         * Does NOT modify any session data.
         *
         * Trigger manually:
         *     SessionCleaner::debugDump();
         *
         * Or via URL: sc_debug=1
         *
         * @return void
         */
        public static function debugDump(): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                echo "Session not active.\n";
                return;
            }

            echo "<pre>";
            echo "SESSION DEBUG (" . date("H:i:s") . ")\n";
            echo "Preserved Keys: " . implode(', ', self::$preserveKeys) . "\n";
            echo str_repeat('-', 60) . "\n";

            foreach ($_SESSION as $key => $value) {
                if (str_starts_with($key, self::TS_PREFIX)) {
                    continue; // Hide internal timestamps from the main list
                }

                $tsKey = self::TS_PREFIX . $key;
                $ts = $_SESSION[$tsKey] ?? null;
                $age = $ts ? (time() - $ts . "s") : "n/a";

                $display = is_scalar($value) ? var_export($value, true) : gettype($value);

                echo "$key: $display";
                echo "   (age: $age)\n";
            }

            echo str_repeat('-', 60) . "\n";
            echo "</pre>";
        }
    }
