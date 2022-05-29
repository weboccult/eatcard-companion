<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Weboccult\EatcardCompanion\Helpers\appDutchDate;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'store_id',
        'user_id',
        'total_price',
        'original_order_total',
        'first_name',
        'last_name',
        'email',
        'contact_no',
        'status',
        'method',
        'comment',
        'order_date',
        'order_id',
        'order_time',
        'order_status',
        'delivery_address',
        'delivery_postcode',
        'delivery_place',
        'delivery_latitude',
        'delivery_longitude',
        'paid_on',
        'order_type',
        'sub_total',
        'total_tax',
        'total_alcohol_tax',
        'delivery_fee',
        'mollie_payment_id',
        'discount',
        'discount_type',
        'discount_amount',
        'is_picked_up',
        'table_name',
        'is_takeaway_mail_send',
        'done_on',
        'alcohol_sub_total',
        'normal_sub_total',
        'dine_in_type',
        'payment_method_type',
        'dine_in_type',
        'ccv_payment_ref',
        'ccv_customer_receipt',
        'checkout_no',
        'kiosk_id',
        'is_untill_order',
        'multisafe_payment_id',
        'is_refunded',
        'coupon_price',
        'gift_purchase_id',
        'payment_split_type',
        'payment_split_persons',
        'additional_fee',
        'created_by',
        'thusibezorgd_order_id',
        'thusibezorgd_res_id',
        'cash_paid',
        'plastic_bag_fee',
        'uber_eats_order_id',
        'worldline_ssai',
        'worldline_customer_receipt',
        'is_delivered',
        'is_uncertain_status',
        'refund_user_id',
        'is_asap',
        'discount_inc_tax',
        'discount_inc_tax_legacy',
        'created_from',
        'is_edited',
        'edited_by',
        'ref_id',
        'is_ignored',
        'all_you_eat_data',
        'ayce_price',
        'statiege_deposite_total',
        'undo_checkout',
        'reservation_paid',
        'saved_order_id',
        'is_base_order',
        'is_paylater_order',
        'tip_amount',
        'direct_order_discount_type',
    ];

    protected $appends = ['full_name', 'generated_date'];

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $msg = 'Order Created: id-'.$model->id.' for the store '.$model->store_id;
            companionLogger($msg.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        });
        static::deleted(function ($model) {
            $msg = 'Order Deleted: id-'.$model->id.' for the store '.$model->store_id.'';
            companionLogger($msg.', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
        });
        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $oldData = $newData = [];
            if (! empty($dirty)) {
                foreach ($dirty as $field => $newdata) {
                    $olddata = $model->getRawOriginal($field);
                    $oldData['old'][$field] = $olddata;
                    $newData['new'][$field] = $newdata;
                }
                $msg = 'Order Updated: id-'.$model->id.' for the store '.$model->store_id;
                companionLogger($msg, $oldData, $newData, ', IP address : '.request()->ip().', browser : '.request()->header('User-Agent'));
            }
        });
    }

    /**
     * @var mixed
     */

    /**
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * @return string
     */
    public function getGeneratedDateAttribute(): string
    {
        $dutch_date = appDutchDate($this->order_date);
        if ($this->order_type === 'dine_in' || $this->order_type === 'kiosk') {
            $text = ($this->dine_in_type == 'take_out') ? 'Meenemen' : (($this->dine_in_type == 'dine_in') ? 'Dine-in' : '');

            return 'direct '.($this->checkout_no ? $this->checkout_no : '').' '.$text;
        } else {
            return $dutch_date.' '.$this->order_time;
        }
    }

    /**
     * @return BelongsTo
     */
    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(KioskDevice::class, 'kiosk_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * @return HasMany
     */
    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class, 'parent_order_id');
    }

    /**
     * @return HasMany
     */
    public function voidOrder(): HasMany
    {
        return $this->hasMany(BackupRestore::class, 'order_id', 'id');
    }
}
