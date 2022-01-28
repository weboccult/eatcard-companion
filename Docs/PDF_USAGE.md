### How to use PDF download and stream ?

```php

Syntax :

__companionPDF("PACKAGE_NAME::FILE_PATH", ["daa"=>$data]);
__companionPDF('eatcard-companion::plain')->#CTRL+SPACE# ==> You will get auto-completion here for available methods

---------------------

# In our case you can use :

__companionPDF('eatcard-companion::plain')->stream()
__companionPDF('eatcard-companion::plain')->download('FILENAME.pdf')

or 

$data = 'dynamic data... anything...'
__companionPDF('eatcard-companion::test-with-data', ['data' => $data])->stream()
__companionPDF('eatcard-companion::test-with-data', ['data' => $data])->download('FILENAME.pdf')
```
