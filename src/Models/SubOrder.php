<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubOrder extends Model
{
    protected $fillable = [
        'parent_order_id',
        'store_id',
        'kiosk_id',
        'alcohol_sub_total',
        'normal_sub_total',
        'sub_total',
        'discount',
        'discount_type',
        'discount_amount',
        'total_tax',
        'total_alcohol_tax',
        'coupon_price',
        'gift_purchase_id',
        'total_price',
        'status',
        'payment_method_type',
        'method',
        'ccv_payment_ref',
        'ccv_customer_receipt',
        'paid_on',
        'split_no',
        'cash_paid',
        'worldline_ssai',
        'worldline_customer_receipt',
        'discount_inc_tax',
        'created_from',
        'statiege_deposite_total',
        'additional_fee',
        'alcohol_product_total',
        'normal_product_total',
    ];

    /**
     * @return BelongsTo
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function subOrderItems(): HasMany
    {
        return $this->hasMany(SubOrderItem::class, 'sub_order_id');
    }
}
