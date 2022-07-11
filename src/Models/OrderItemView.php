<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Models\Product;

class OrderItemView extends Model
{
    use HasFactory;

    public $table = 'order_item_views';

    protected $guarded = [];

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id', 'id');
	}

	public function order()
	{
		return $this->belongsTo(OrderView::class, 'id', 'order_id');
	}

}
