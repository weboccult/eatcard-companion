### Basic Usage

- $inputs : received from frontend side without "items" field. 
- $cart : decoded "items" field received from frontend side.

```php
use function Weboccult\EatcardCompanion\Helpers\eatcardOrder;

eatcardOrder()
    ->processor(PosProcessor::class)
    ->system(SystemTypes::POS)
    ->payload($inputs)
    ->cart($cart)
    ->dispatch()

or 

use Weboccult\EatcardCompanion\Services\Core\EatcardOrder;

EatcardOrder::
    processor(PosProcessor::class)
    ->system(SystemTypes::POS)
    ->payload($payload)
    ->cart($cart)
    ->dispatch()
```
