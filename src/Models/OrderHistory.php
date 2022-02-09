<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Weboccult\EatcardCompanion\Helpers\appDutchDate;

class OrderHistory extends Model
{
    use SoftDeletes;

    protected $table = 'orders_history';

    protected $appends = ['full_name', 'generated_date'];
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
        return $this->hasMany(OrderItemHistory::class, 'order_id');
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
