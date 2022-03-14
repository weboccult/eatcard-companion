### Basic Usage of untill service

- You don't have to write xml everywhere and you can access nice simplied ready made API in companion package.


### How to use

```php

#------------------------------
# Generic Untill API usage with manual template build and dispatch API
#------------------------------

use Weboccult\EatcardCompanion\Services\Facades\Untill


Untill::store($storeEloquentInstance)
       ->table($tableEloqunetInstance)
       ->build('TEMPLATE_NAME', [])
       ->dispatch();

# Example manual build and dispatch

Untill::store($storeEloquentInstance)
        ->table($tableEloquentInstance)
        ->build('GetActiveTableInfo.xml', [
            'USER_NAME' => $this->store->untillSetting->untill_username,
            'PASSWORD' => $this->store->untillSetting->untill_password,
            'APP_TOKEN' => config('eatcardCompanion.untill.app_token'),
            'APP_NAME' => config('eatcardCompanion.untill.app_name'),
        ])
        ->dispatch();


# Note :
# you can find available templates in Services/Common/Untill/XMLRequest Directory
# In the second argument you can pass variables, which you need to replace in the XML template


        
#------------------------------
# Table Info
#------------------------------

Untill::store($storeEloquentInstance)
       ->table($tableEloquentInstance)
       ->getActiveTableInfo()
       ->dispatch()
       
#------------------------------
# Table Items Info
#------------------------------

Untill::store($storeEloquentInstance)
       ->table($tableEloquentInstance)
       ->getTableItemsInfo()
       ->dispatch()
       

#------------------------------
# Get payments Info
#------------------------------

Untill::store($storeEloquentInstance)
       ->getPaymentsInfo()
       ->dispatch()
       
#------------------------------
# Close Order
#------------------------------

Untill::store($storeEloquentInstance)
       ->table($tableEloquentInstance)
       ->closeOrder() 
       // closeOrder method ==> Under the hood it will just build template and set required things like
       // $this->build('CloseOrder.xml')->setCredentials()->setTableNumber();
       ->setPaymentId(5000050)
       ->dispatch()
       
#------------------------------
# Create Order
#------------------------------

Untill::store($storeEloquentInstance)
       ->table($tableEloquentInstance)
       ->createOrder([
            ['untill_id' => 12312312312, 'quantity' => 1],
            ['untill_id' => 56756756733, 'quantity' => 2],
       ])
       // createOrder method ==> Under the hood it will just build template and set required things like prepare 
       // order nd order item xml data
       ->setPersons(2)
       ->setFirstName('sdads')
       ->dispatch()       
       
#------------------------------
# Response Helpers : It will provide simple and easy to ready-made helper to get return code and return message
#------------------------------
Untill::getReturnCode($requestName, $response)
Untill::getReturnMessage($requestName, $response)
 
// Example
Untill::getReturnCode('GetPaymentsInfo', $response);
Untill::getReturnMessage('GetPaymentsInfo', $response);
 
// Example
Untill::getOutput('GetTableItemsInfo', 'Items.item', $response);
// GetActiveTableInfo ==> Transaction.Orders
// GetPaymentsInfo ==> Payments Or Payments.item.*.PaymentId
// GetTableItemsInfo ==> Items.ite
```
