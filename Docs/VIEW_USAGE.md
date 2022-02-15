### Where to put view files ?

- you can create new view files or modify translation files which is available in ``resources/views`` folder.

### How to override view files in parent project ?

here's how you can use publish companion view files to override the content/html.

```php
php artisan eatcardcompanion:publish --type=views
```
It will publish multiple view files to your parent project and after that you can override content/html in parent 
project where this package is installed.

### How to use view ?

You can use view() or other standard helper function which is built in laravel itself.

```php

Syntax :

view("PACKAGE_NAME::FILE_PATH", ["daa"=>$data]);
view("PACKAGE_NAME::FILE_PATH", compact('data'));


---------------------

# In our case you can use :

view('eatcard-companion::plain')

or 

$data = 'dynamic data... anything...'
view('eatcard-companion::test-with-data', ['data' => $data])
view('eatcard-companion::test-with-data', compact('data'))


---------------------
```

New helpers added : 

- So don't have to write package name everytime when you render the view.
- helper method with return view object so you need to call render() method.

```php
__companionViews('plain')->render()
__companionViews('test-with-data', $data)->render()

```
