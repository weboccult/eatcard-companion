### How to use PDF download and stream ?

```php

Syntax :

__companionPDF("FILE_PATH", ["daa"=>$data]);
__companionPDF('plain')->#CTRL+SPACE# ==> You will get auto-completion here for available methods

---------------------

# In our case you can use :

__companionPDF('plain')->stream()
__companionPDF('plain')->download('FILENAME.pdf')

or 

$data = 'dynamic data... anything...'
__companionPDF('test-with-data', ['data' => $data])->stream()
__companionPDF('test-with-data', ['data' => $data])->download('FILENAME.pdf')
```
