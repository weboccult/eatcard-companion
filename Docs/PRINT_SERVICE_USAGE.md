### Basic Usage of print service

- The print service generates ``json`` for print, ``html``, and ``pdf`` recipes for orders.


### Print generators / Order Type
- Print generators are simply a type of order, with different print data based upon that.
1. ``PAID`` : Paid Orders
2. ``RUNNING`` : Running Orders (unpaid orders for Proforma Print)
3. ``SAVE`` : Save Orders
4. ``SUB`` : Sub Orders (Split payments)

### Print Methods
- Prints method define the output of request
1. ``SQS``: This method defines data in json format.
2. ``Protocol`` : This method defines data in json format.
3. ``HTML`` : This method defines data in json format.
4. ``PDF`` : This method defines data in PDF file download.

### Print Type
1. ``DEFAULT`` : It will print Main,Kitchen,Label print based on setting.
2. ``MAIN`` : It will print only Full Receipt.
3. ``KITCHEN`` : It will print only Kitchen Print.
4. ``LABEL`` : It will print only Label Print.
5. ``KITCHEN_LABEL`` : It will print Both Kitchen & Label Print.
6. ``PROFORMA`` : It will print only Proforma Print.
7. ``MAIN_KITCHEN_LABEL`` It will print Main,Kitchen,Label print.

### SystemTypes
- The platform where orders are placed is defined by the system type.
- Some settings are based on system type.

###Payload
- Mandatory data which are related to ``Print Method`` and ``Print Type`` is need to send in the payload.
Like
1. order_id
2. store_id
3. takeawayEmailType
4. Protocol print's all request data.


### How to use

```php

#------------------------------
# For SQS, HTML, PDF Print Method.
#------------------------------

use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;

EatcardPrint::generator(PaidOrderGenerator::class)
        ->method(PrintMethod::SQS)
        ->type(PrintTypes::DEFAULT)
        ->system(SystemTypes::POS)
        ->payload(['order_id'=>''.$id])
        ->generate();
        
#------------------------------
# For Protocol Method.
#------------------------------
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;

EatcardPrint::payload($payload)->generate();
# For Protocol print, all things will define base of payload data, so no need to set other things

```
