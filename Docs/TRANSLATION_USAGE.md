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

Note : second argument is completely optional.

```php

Syntax :

__('PACKAGE_NAME::FILE.LINE', ['attributes' => 'value'])
trans('PACKAGE_NAME::FILE.LINE', ['attributes' => 'value'])

---------------------

__('eatcard-companion::general.test', ['attributes' => 'value'])

or

trans('eatcard-companion::general.test', ['attributes' => 'value'])

---------------------
```

New helpers added : 

- both function have their setting in [EATCARD_COMPANION.md](Docs/EATCARD_COMPANION.md)
- So you can enable or disable translation

```php
__companionTrans('general.test', ['attributes' => 'value'])
__companionPrintTrans('general.test', ['attributes' => 'value'])

```
