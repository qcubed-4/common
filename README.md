# QCubed-4 Common Library
Common classes for the entire QCubed-4 framework.

There are several repositories:

* **qcubed-4/application** — the core QCubed functionality: forms, controls, UI rendering
* **qcubed-4/orm** — the object-relational mapper enabling powerful queries
* **qcubed-4/i18n** — translation and localization services
* **qcubed-4/cache** — caching layers for QCubed
* **qcubed-4/app-starter** — a quick-start template to begin new QCubed projects
* **qcubed-4/common** — shared classes used across QCubed-4 repositories
* **qcubed-4/bootstrap** — Bootstrap integration for QCubed

---

## SessionCleaner Utility

`SessionCleaner` is a lightweight helper that automatically manages and cleans temporary PHP session keys.

This utility is extremely helpful in workflows such as:

- image uploads & avatar croppie handlers
- wizard / multi-step forms
- modal-based temporary actions
- any UI process that stores short-lived state

### Key Features

- **Automatic cleanup** of expired session keys
- **Protected keys** via `setPreserveKeys()`
- **Timestamp-based expiration tracking**
- **One-line global cleanup** with `autoClean()`
- **Developer debug mode**
    - Direct call:
      ```php
      SessionCleaner::debugDump();
      ```
    - OR via URL parameter:
      ```
      ?sc_debug=1
      ```

### ✔ Usage Example

```php
use QCubed\Helper\SessionCleaner;

// Session keys that must never be removed
SessionCleaner::setPreserveKeys(['logged_user_id', 'csrf_token', 'qformstate']);

// Automatically remove temporary session keys older than 30 minutes
SessionCleaner::autoClean(1800);
```
More details can be found in: docs/SessionCleaner.md