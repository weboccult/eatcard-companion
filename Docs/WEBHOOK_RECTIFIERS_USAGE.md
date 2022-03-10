### Basic Usage

- $orderId 
- $storeId
- $orderType : It will be optional and used only for sub_order
- action : here you have to insert class based on your project and action type (webhook and redirect)

## DineIn Webhook + Redirection

```php
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\EatcardWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MollieDineInOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MollieDineInOrderWebhook;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderCancelRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderSuccessRedirect;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\DineIn\MultiSafeDineInOrderWebhook;

// Webhook
try {
    $payload = $request->all();
    $storeId = 1;
    $orderId = 1;
    // $returnedData = EatcardWebhook::action(MollieDineInOrderWebhook::class)
    $returnedData = EatcardWebhook::action(MultiSafeDineInOrderWebhook::class)
        ->payload($payload)
        ->setStoreId($storeId)
        ->setOrderId($orderId)
        ->dispatch();
} catch (\Exception $e) {

}

// Redirect : Success / Cancel
try {
    $payload = $request->all();
    $storeId = 1;
    $orderId = 1;
    // $returnedData = EatcardWebhook::action(MollieDineInOrderSuccessRedirect::class)
    // $returnedData = EatcardWebhook::action(MultiSafeDineInOrderSuccessRedirect::class)
    $returnedData = EatcardWebhook::action(MultiSafeDineInOrderCancelRedirect::class)
        ->payload($payload)
        ->setStoreId($storeId)
        ->setOrderId($orderId)
        ->dispatch();
} catch (\Exception $e) {

}
```
### Other system's Syntax will be added here soon...
