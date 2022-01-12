<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Services\Common\Orders\Traits\MagicAccessors;

/**
 * @description Stag 6
 * @mixin MagicAccessors
 *
 * @author Darshit Hedpara
 */
trait Stage6preparePostValidationRulesAfterDatabaseInteraction
{
    protected function storeExistValidation()
    {
    }

    protected function reservationAlreadyCheckoutValidation()
    {
    }

    protected function duplicateProductDetectedOnCart()
    {
        $product_cart_id = [];
        foreach ($this->cart as $key => $item) {
            if (count($product_cart_id) > 0 && in_array($item['cartId'], $product_cart_id)) {
                $this->setDumpDieValue(['same_cart_id' => 'Duplicate product added on cart. Please empty your cart and try again.']);
            } else {
                $product_cart_id[] = $item['cartId'];
            }
        }
    }
}
