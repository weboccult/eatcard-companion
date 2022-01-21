### Where to put translation files ?

- you can create new translation files or modify translation files which is available in ``resources/lang`` folder.

### How to override translation files in parent project ?

here's how you can use publish companion translation files to override messages.

```php
php artisan eatcardcompanion:publish --type=translations
```
It will publish multiple translations files to your parent project and after that you can override message in parent 
project where this package is installed.

### How to use translations ?

You can use __() or trans() helper function which is built in laravel itself.

```php

Syntax :

__('PACKAGE_NAME::FILE.LINE')
trans('PACKAGE_NAME::FILE.LINE')

---------------------

__('eatcard-companion::general.test')

or

trans('eatcard-companion::general.test')

---------------------
```

New helpers added : 

- both function have their setting in [EATCARD_COMPANION.md](Docs/EATCARD_COMPANION.md)
- So you can enable or disable translation

```php
__companionTrans('general.test')
__companionPrintTrans('general.test')

```
