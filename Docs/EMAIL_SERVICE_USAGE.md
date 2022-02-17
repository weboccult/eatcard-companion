### Basic Usage

- $inputs : received from frontend side without "items" field. 
- $cart : decoded "items" field received from frontend side.

```php
use function Weboccult\EatcardCompanion\Helpers\eatcardEmail;

eatcardEmail()
    ->entityType('as')
    ->entityId('1')
    ->email('ab@gmail.com')
    ->cc([])
    ->bcc([])
    ->mailType('asdas')
    ->mailFromName('asdas')
    ->subject('1231')
    ->content('HTML or text any thing...')
    ->dispatch();

or 

use Weboccult\EatcardCompanion\Services\Core\EatcardEmail;

EatcardEmail::
    entityType('as')
    ->entityId('1')
    ->email('ab@gmail.com')
    ->cc([])
    ->bcc([])
    ->mailType('asdas')
    ->mailFromName('asdas')
    ->subject('1231')
    ->content('HTML or text any thing...')
    ->dispatch();
```
