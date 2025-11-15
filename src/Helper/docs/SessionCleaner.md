# QCubed SessionCleaner Utility

A lightweight utility for managing and automatically cleaning temporary PHP session keys inside QCubed-4 applications.

Useful for:

- upload & croppie handlers

- temporary UI actions

- modal workflows

- wizard steps

- restoring abandoned or expired page states

- any short-lived session data

---
## Overview

SessionCleaner automatically tracks temporary session keys using timestamps and removes them when they expire.
It safeguards important session keys (like logged-in user data) using a protected list.

This tool helps keep your PHP sessions clean, predictable, and fast — especially in long-lived backend sessions.

## Namespace
```php
use QCubed\Helper\SessionCleaner;
```

## Features

### ✔ Automatic Session Cleanup

Cleans all expirable session keys older than a given TTL (seconds).

### ✔ Protected Keys

You can mark session names that must never be deleted.

### ✔ Timestamp Tracking

Each temporary session key has a parallel timestamp entry stored automatically.

### ✔ Two Cleanup Modes

1. Manual cleanup — call clean()

2. Automatic cleanup — call autoClean()

### ✔ Built-in Debugging

- Directly show all session keys & their timestamps

- Optional debug mode via URL parameter (?sc_debug=1)

### ✔ Safe by Default

- Preserved keys are never touched

- Non-registered or manually created session data is unaffected

# Basic Usage

### Protect important keys

These keys will never be deleted:

```php
SessionCleaner::setPreserveKeys(['logged_user_id', 'csrf_token', 'qformstate']);
```

### Clean temporary sessions older than 30 minutes:

```php
SessionCleaner::clean(1800);
```

### Automatically clean (recommended)

Calls cleanup immediately and on every request:

```php
SessionCleaner::autoClean(1800);
```

## Marking a Temp Session Key

A session key becomes “temporary” when you call:

```php
SessionCleaner::mark('user_id');
```

After marking, a timestamp is automatically created:
```
user_id
sc_ts_user_id
```
Later, clean() will remove them if expired.

### Example
```php
$_SESSION['upload_id'] = 33;
SessionCleaner::mark('upload_id');
```

-------------

## Full Cleanup Example (Croppie / Avatar Upload)

```php
// The user begins uploading/changing an avatar
$_SESSION['user_id'] = 14;
SessionCleaner::mark('user_id');

// Also track the previous avatar
$_SESSION['user_old_avatar'] = 'john.png';
SessionCleaner::mark('user_old_avatar');

// Auto-clean all old sessions (e.g. from abandoned uploads)
SessionCleaner::autoClean(1800);
```

## Debugging

### Directly calling debugDump()

```php
SessionCleaner::debugDump();
```

### OR enabling via URL

Add this to any page:

```
?sc_debug=1
```

You will see:

```
SESSION DEBUG (14:32:21)
Preserved Keys: logged_user_id, csrf_token, qformstate
--------------------------------------------------
logged_user_id: 1
csrf_token: xyz...
article: 650
sc_ts_article: 1710000123
--------------------------------------------------
```

### Important Note When Using Global autoClean()

If you choose to run:

```php
SessionCleaner::autoClean(1800);
```

globally in your application (for example in `header.inc.php` or a base form), you **must explicitly define which session keys should be preserved**.

QCubed-4 relies on two core session variables:

- `csrf_token`

- `qformstate`

These are essential for secure and stable framework operation, and **must never be removed automatically**.

Therefore, you should define:

```php
SessionCleaner::setPreserveKeys(['csrf_token', 'qformstate']);
```

If your application uses additional permanent session keys — for example:

- `logged_user_id`
- `user_role`
- `language`
- etc.

make sure to include them as well, so that SessionCleaner does not delete them during automatic cleanup.

### API Reference

| Method                                                | Description                                                                   |
|-------------------------------------------------------|-------------------------------------------------------------------------------|
| **setPreserveKeys(array $keys): void**                | Defines keys that must never be removed.                                      |
| **mark(string $key): void**                           | Marks a session key as temporary and registers a timestamp.                   |
| **clean(int $ttlSeconds): void**                      | Removes all temporary session keys older than the given TTL.                  |
| **autoClean(int $ttlSeconds): void**                  | Runs cleanup immediately + ensures cleanup is executed once on every request. |
| **debugDump(bool $return = false): string \| null**   | Outputs or returns the current debug information.                             |


### Example Integration (Recommended)

Place this in your backend header, e.g. BasePage, layout include, or controller bootstrap:

```php
SessionCleaner::setPreserveKeys(['logged_user_id', 'csrf_token']);
SessionCleaner::autoClean(1800); // clean every 30 minutes
```

- Zero maintenance.
- Always clean.
- Always safe.

## Summary

SessionCleaner keeps QCubed projects fast, clean, and resource-efficient, especially for complex admin interfaces that rely on temporary session data.

This component is safe to use globally and can dramatically reduce leftover session clutter created by user interruptions, modal cancellations, or unfinished workflows.

## License

MIT © 2025 QCubed-4 Common Utilities Team
Maintained by the QCubed-4 open-source community.