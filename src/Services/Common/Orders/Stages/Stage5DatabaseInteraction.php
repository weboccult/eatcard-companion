<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @description Stag 5
 * @mixin BaseProcessor
 */
trait Stage5DatabaseInteraction
{
    protected function setProductData()
    {
        $product_ids = collect($this->cart)->pluck('id')->toArray();
        companionLogger('Product ids extracted from cart', $product_ids);
        $this->productData = Product::withTrashed()->with([
            'category' => function ($q1) {
                $q1->withTrashed();
                $q1->select('id', 'tax');
            },
            'ayce_class',
        ])->whereIn('id', $product_ids)->get();
        companionLogger('Product data fetched from database', $this->productData);
    }

    protected function setSupplementData()
    {
    }
}
