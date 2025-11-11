<?php
    /**
     * QCubed-4 Common Utility
     *
     * Class: SessionCleaner
     * ---------------------
     * A lightweight helper for managing and automatically cleaning up
     * short-lived PHP session keys while preserving critical application
     * sessions like logged user IDs and CSRF tokens.
     *
     * © 2025 QCubed-4 Common Utilities Team
     */

    namespace QCubed\Helper;

    use DateTime;

    /**
     * Class SessionCleaner
     *
     * @package QCubed\Helper
     */
    class SessionCleaner
    {
        /** @var array Keys that should never be deleted */
        protected static array $preserveKeys = [];

        /** @var bool Debug output enabled */
        protected static bool $debug = false;

        /** @var bool Automatically dump session contents after cleaning */
        protected static bool $autoDump = false;

        /** @var int Max value length shown in logs */
        protected static int $maxLogValueLength = 200;

        /** @var string Session key to store cleaner metadata */
        protected const string META_KEY = '__session_cleaner_meta';

        /**
         * Marks the start time of a temporary session.
         */
        public static function markCreated(): void
        {
            self::ensureSession();

            $_SESSION[self::META_KEY]['created'] = time();
        }

        /**
         * Defines keys that must never be deleted.
         *
         * @param array $keys
         */
        public static function setPreserveKeys(array $keys): void
        {
            self::$preserveKeys = array_unique($keys);
        }

        /**
         * Enables or disables debug mode.
         *
         * @param bool $debug
         * @param bool $autoDump Automatically print session after cleaning
         */
        public static function setDebugMode(bool $debug, bool $autoDump = false): void
        {
            self::$debug = $debug;
            self::$autoDump = $autoDump;
        }

        /**
         * Sets the maximum length of session values printed in debug output.
         *
         * @param int $length
         */
        public static function setMaxLogValueLength(int $length): void
        {
            self::$maxLogValueLength = max(20, $length);
        }

        /**
         * Automatically cleans specified keys after a given lifetime.
         *
         * @param array $keys
         * @param int $maxAge Lifetime in seconds
         */
        public static function autoClean(array $keys, int $maxAge): void
        {
            self::ensureSession();
            self::clean($keys, $maxAge);
        }

        /**
         * Cleans keys older than a given lifetime.
         *
         * @param array $keys
         * @param int $maxAge Lifetime in seconds
         */
        public static function clean(array $keys, int $maxAge): void
        {
            self::ensureSession();

            $meta = $_SESSION[self::META_KEY] ?? ['created' => time()];
            $created = $meta['created'] ?? time();

            if (time() - $created >= $maxAge) {
                foreach ($keys as $key) {
                    if (!in_array($key, self::$preserveKeys, true) && isset($_SESSION[$key])) {
                        unset($_SESSION[$key]);
                        self::log("Removed expired key: $key");
                    }
                }
                $_SESSION[self::META_KEY]['created'] = time(); // reset timer
            }

            if (self::$autoDump) {
                self::debugDump();
            }
        }

        /**
         * Immediately deletes the given session keys.
         *
         * @param array $keys
         */
        public static function forceClean(array $keys): void
        {
            self::ensureSession();

            foreach ($keys as $key) {
                if (!in_array($key, self::$preserveKeys, true) && isset($_SESSION[$key])) {
                    unset($_SESSION[$key]);
                    self::log("Force removed key: $key");
                }
            }

            if (self::$autoDump) {
                self::debugDump();
            }
        }

        /**
         * Prints a formatted dump of the current session state.
         */
        public static function debugDump(): void
        {
            if (!self::$debug) {
                return;
            }

            self::ensureSession();

            echo "\nSESSION DEBUG (" . date('H:i:s') . ")\n";
            echo "Preserved Keys: " . implode(', ', self::$preserveKeys) . "\n";
            echo str_repeat('-', 50) . "\n";

            foreach ($_SESSION as $key => $value) {
                if ($key === self::META_KEY) {
                    continue;
                }

                $display = is_scalar($value) ? (string)$value : json_encode($value);
                if (strlen($display) > self::$maxLogValueLength) {
                    $display = substr($display, 0, self::$maxLogValueLength) . '...';
                }

                echo "$key: $display\n";
            }

            echo str_repeat('-', 50) . "\n";
        }

        /**
         * Ensures that a PHP session is active.
         */
        protected static function ensureSession(): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
        }

        /**
         * Logs a message if debug mode is enabled.
         *
         * @param string $message
         */
        protected static function log(string $message): void
        {
            if (self::$debug) {
                error_log('[SessionCleaner] ' . $message);
            }
        }
    }
