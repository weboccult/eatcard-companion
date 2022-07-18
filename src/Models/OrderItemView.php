<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\ProductView;

class OrderItemView extends Model
{
    use HasFactory;

    public $table = 'order_item_views';

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(ProductView::class, 'id', 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(OrderView::class, 'id', 'order_id');
    }
}
