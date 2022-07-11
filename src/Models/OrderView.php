<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Models\BackupRestore;
use Weboccult\EatcardCompanion\Models\KioskDevice;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\SubOrder;

class OrderView extends Model
{
    use HasFactory;

    protected $table = 'orders_views';

    public $guarded = [];

    public function orderItems()
    {
        return $this->hasMany(OrderItemView::class, 'order_id', 'id');
    }

    public function voidOrder()
    {
        return $this->hasMany(BackupRestore::class, 'order_id', 'id');
    }

    public function kioskDevice()
    {
        return $this->hasOne(KioskDevice::class, 'id', 'kiosk_id');
    }

    public function subOrders()
    {
        return $this->hasMany(SubOrder::class, 'parent_order_id', 'id');
    }

    public function reservation()
    {
        return $this->belongsTo(StoreReservation::class, 'id', 'parent_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}