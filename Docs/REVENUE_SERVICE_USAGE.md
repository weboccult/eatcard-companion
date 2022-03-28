### Basic Usage of revenue service

- The revenue service generates revenue data  in ``json`` format for print, ``html``, and ``pdf``.


### Revenue generators / Revenue Type
- Revenue generators are simply a type of revenue, with different criteria of data based upon that.
1. ``DAILY`` : single day revenue data
2. ``MONTHLY`` : single month revenue data

### Print Methods
- Prints method define the output of request
1. ``SQS``: This method defines data in json format.
2. ``Protocol`` : This method defines data in json format.
3. ``HTML`` : This method defines data in json format.
4. ``PDF`` : This method defines data in PDF file download.

###Date, Month and Year
- Date : set for get Daily revenue, data in format of ``dd/MM/yyyy``
- Month : set for get Monthly revenue data in format of ``MM``
- Year : set for get Monthly revenue data in format of ``yyyy``


###Payload
- Mandatory data which are related to ``Revenue Method`` and ``Print Type`` is need to send in the payload.
Like
1. store_id
2. date
3. month
4. year
5. request_type.


### How to use

```php

#------------------------------
# For SQS, HTML, PDF Print Method.
#------------------------------

use Weboccult\EatcardCompanion\Services\Common\Revenue\Generators\DailyRevenueGenerator;
use Weboccult\EatcardCompanion\Enums\PrintMethod;

EatcardRevenue::generator(Revenue\Generators\DailyRevenueGenerator::class)
                ->method(PrintMethod::HTML)
                ->date($date)
                ->payload(['store_id' => ''. $store_id])
                ->generate();
        
#------------------------------
# For Protocol Method.
#------------------------------
use Weboccult\EatcardCompanion\Services\Common\Revenue\Generators\DailyRevenueGenerator;

EatcardRevenue::payload($payload)->generate();
# For Protocol print, all things will define base of payload data, so no need to set other things

```
