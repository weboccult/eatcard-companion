<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Models\Category;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 6
 * @mixin BaseGenerator
 */
trait Stage6DatabaseInteraction
{
    protected function setCategoryData()
    {
        if ($this->orderType == OrderTypes::PAID) {
            $categoriesIds = collect(($this->order['order_items'] ?? []))->pluck('product')->pluck('category_id')->toArray();
//               $categoriesIds = collect($categoriesIds)->pluck('category_id')->toArray();
        } elseif ($this->orderType == OrderTypes::RUNNING) {
            //coming soon
        } elseif ($this->orderType == OrderTypes::SUB) {
            //coming soon
        } elseif ($this->orderType == OrderTypes::SAVE) {
            //coming soon
        }

        $categories = Category::withTrashed()->select(['*', \DB::raw('IF(`order` IS NOT NULL, `order`, 1000000) `order`')])
                            ->whereIn('id', $categoriesIds)
                            ->where('store_id', $this->store->id)
                            ->orderBy('order', 'asc')
                            ->get();

        companionLogger('--Eatcard companion categories details : ', $categories);
        $this->categories = $categories;
    }
}
