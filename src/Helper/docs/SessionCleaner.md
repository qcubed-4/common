# QCubed SessionCleaner Utility

The **SessionCleaner** class is a lightweight helper for the **QCubed-4** framework.  
It provides safe, controllable cleanup for temporary PHP sessions — while protecting long-term sessions such as user logins or CSRF tokens.

---

## Overview

**Namespace:**
```php
use QCubed\Helper\SessionCleaner;
```
**File Location:**
common/src/Helper/SessionCleaner.php

## Main Features:

- Prevent stale session data from lingering in memory

- Automatically expire short-lived session keys (e.g., user_id, upload_token)

- Preserve essential keys like logged_user_id or csrf_token

- Work seamlessly with QCubed-4, AJAX, and cron jobs

- Provide optional debugging and session inspection tools

# Basic Usage

```php
use QCubed\Helper\SessionCleaner;

// Define which session keys must never be deleted
SessionCleaner::setPreserveKeys(['logged_user_id', 'csrf_token']); //etc...

// Enable debug mode and automatic dump after cleanup
SessionCleaner::setDebugMode(true, true);

// Mark the creation time for this session
SessionCleaner::markCreated();

// Example temporary sessions
$_SESSION['user_id'] = 5;
$_SESSION['user_old_avatar'] = 'avatar_123.png';

// Automatically remove these keys after 30 minutes (1800 seconds)
SessionCleaner::autoClean(['user_id', 'user_old_avatar'], 1800);
```
## Advanced Control

### Force Delete Specific Keys

Immediately remove session values, regardless of age:

```php
SessionCleaner::forceClean(['user_id', 'user_old_avatar']);
```

### Conditional Clean by Lifetime

Check and remove session keys if they are older than 10 minutes:
```php
SessionCleaner::clean(['temp_upload'], 600);
```
### Define Permanent (Preserved) Keys Globally
```php
SessionCleaner::setPreserveKeys([
    'logged_user_id',
    'csrf_token',
    'admin_mode'
]);
```
### Method Reference

| Method                                                | Description                                                                          |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------ |
| **markCreated()**                                     | Marks the start timestamp for temporary session data.                                |
| **autoClean(array $keys, int $maxAge)**               | Automatically removes specified keys if the session is older than `$maxAge` seconds. |
| **clean(array $keys, int $maxAge)**                   | Manually checks age and removes expired keys.                                        |
| **forceClean(array $keys)**                           | Immediately deletes the given session keys.                                          |
| **setPreserveKeys(array $keys)**                      | Defines keys that will never be deleted by cleaner operations.                       |
| **setDebugMode(bool $debug, bool $autoDump = false)** | Enables debug output and optional automatic session dump.                            |
| **setMaxLogValueLength(int $length)**                 | Limits the length of printed session values in debug output.                         |
| **debugDump()**                                       | Prints a formatted list of all current session values.                               |


### Example Debug Output

```yaml
SESSION DEBUG (15:42:18)
Preserved Keys: logged_user_id, csrf_token
--------------------------------------------------
logged_user_id: 42
csrf_token: B3A7D9-XY55
user_id: [expired and removed]
user_old_avatar: [expired and removed]
--------------------------------------------------
```
##  Important Notes

- **Preserved keys** will never be deleted — even if forceClean() is called.
- Fully compatible with Application::startSession() and does not override QCubed’s native session handler.
- Perfect for cleaning temporary sessions from:
  - Modal dialogs
  - One-time user interactions
- Can also be used inside cron jobs or background scripts.

### Example Integration (Croppie Uploads)

In avatar_upload.php:

```php
use QCubed\Helper\SessionCleaner;

// Keep only the logged-in user session
SessionCleaner::setPreserveKeys(['logged_user_id']);

// Automatically clear user upload sessions after 30 minutes
SessionCleaner::autoClean(['user_id', 'user_old_avatar'], 1800);
```

### Debugging Options

To enable real-time logging:
```php
SessionCleaner::setDebugMode(true);
```

To print current session content automatically after each cleaning:
```php
SessionCleaner::setDebugMode(true, true);
```

### Internal Metadata

SessionCleaner uses an internal key `__session_cleaner_meta`
to store timestamps and manage expiry detection.

This key is handled automatically and should not be modified manually.

## License

MIT © 2025 QCubed-4 Common Utilities Team
Maintained by the QCubed-4 open-source community.