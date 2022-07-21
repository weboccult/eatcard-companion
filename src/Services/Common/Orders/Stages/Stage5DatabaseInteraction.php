<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Services\Common\Orders\BaseProcessor;

/**
 * @description Stag 5
 * @mixin BaseProcessor
 *
 * @author Darshit Hedpara
 */
trait Stage5DatabaseInteraction
{
    protected function setProductData()
    {
        if ($this->system == SystemTypes::POS && $this->isSubOrder) {
            $product_ids = collect($this->originalCart)->pluck('id')->toArray();
        } else {
            // Other systems : POS | Waitress | Takeaway | Kiosk
            $product_ids = collect($this->cart)->pluck('id')->toArray();
        }
        $this->productData = Product::withTrashed()->with([
            'category' => function ($q1) {
                $q1->withTrashed();
                $q1->select('id', 'tax');
            },
            'ayce_class',
        ])->whereIn('id', $product_ids)->get();
    }

    protected function setSupplementData()
    {
        if ($this->system == SystemTypes::POS && $this->isSubOrder) {
            $supplement_ids = collect($this->originalCart)->pluck('supplements')->flatten(1)->pluck('id')->filter()->toArray();
        } else {
            // other systems : POS | Waitress | Takeaway
//            $supplement_ids = collect($this->cart)->pluck('supplements.id')->filter()->toArray();
            $supplement_ids = collect($this->cart)->pluck('supplements')->flatten(1)->pluck('id')->filter()->toArray();
        }
        $this->supplementData = Supplement::withTrashed()->whereIn('id', $supplement_ids)->get();
    }
}
