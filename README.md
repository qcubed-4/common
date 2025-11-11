# QCubed-4 Common Library
Common classes for all the QCubed framework - v4

There are several repositories. 
* qcubed-4/application: the core functionality of QCubed-4. Building forms with textboxes, labels, buttons, etc...

* qcubed-4/orm: the object relational mapper of QCubed that allows for powerful queries to be executed with ease.

* qcubed-4/i18n: the translator services of QCubed

* qcubed-4/cache: the caching mechanisms for QCubed

* qcubed-4/app-starter: this repository allows you to quickly start a new QCubed-4 project

* qcubed-4/common: a collection of classes that are used throughout the different QCubed-4 repositories.

* qcubed-4/bootstrap: include bootstrap in your QCubed-4 project

## SessionCleaner Utility

`SessionCleaner` is a built-in helper class that keeps your temporary PHP sessions clean and secure — especially useful for upload handlers, croppie integrations, or temporary modal actions.

**Namespace:**
```php
use QCubed\Helper\SessionCleaner;

