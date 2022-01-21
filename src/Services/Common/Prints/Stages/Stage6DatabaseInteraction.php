<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PaymentSplitTypes;
use Weboccult\EatcardCompanion\Models\Category;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 6
 * @mixin BaseGenerator
 */
trait Stage6DatabaseInteraction
{
    /**
     * @return void
     * set categories data
     * category id will be got from related order Item details
     */
    protected function setCategoryData()
    {
        $categoriesIds = [];
        if (in_array($this->orderType, [OrderTypes::PAID, OrderTypes::SUB])) {
            if ($this->orderType == OrderTypes::SUB && isset($this->order['payment_split_type']) && $this->order['payment_split_type'] == PaymentSplitTypes::PRODUCT_SPLIT) {
                $categoriesIds = collect(($this->subOrder['sub_order_items'] ?? []))->pluck('product')->pluck('category_id')->toArray();
            } else {
                $categoriesIds = collect(($this->order['order_items'] ?? []))->pluck('product')->pluck('category_id')->toArray();
                //               $categoriesIds = collect($categoriesIds)->pluck('category_id')->toArray();
            }
        } elseif ($this->orderType == OrderTypes::RUNNING) {
            if (! empty($this->reservationOrderItems)) {
                $cart = json_decode($this->reservationOrderItems->cart, true);
                /* get category and products of the store */
                $categoriesIds = collect($cart)->pluck('category');
                $categoriesIds = collect($categoriesIds)->pluck('id')->toArray();
            }

            if (isset($this->proformaProducts) && ! empty($this->proformaProducts)) {
                $categoriesIds = collect($this->proformaProducts)->pluck('category');
                $categoriesIds = collect($categoriesIds)->pluck('id')->toArray();
            }
        } elseif ($this->orderType == OrderTypes::SAVE) {
            if (isset($this->saveOrderProducts) && ! empty($this->saveOrderProducts)) {
                $categoriesIds = collect($this->saveOrderProducts)->pluck('category');
                $categoriesIds = collect($categoriesIds)->pluck('id')->toArray();
            }
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
