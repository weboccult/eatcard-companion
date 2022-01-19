<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Traits\Translatable;
use function Weboccult\EatcardCompanion\Helpers\getS3File;

class GiftCard extends Model
{
    use Translatable;

    protected $fillable = [
        'store_id',
        'user_id',
        'name',
        'description',
        'price',
        'image',
        'color',
        'is_available',
        'is_multi_usage',
        'order',
    ];

    protected $appends = [
        'image_url',
        'gift_price',
    ];

    public $translatableFields = [
        'name',
        'description',
    ];

    /**
     * @return mixed|string
     */
    public function getImageUrlAttribute()
    {
        if ($this->image && file_exists(public_path($this->image))) {
            return asset('imagecache/GiftCardMediumImage/'.getCachedImagePath($this->image));
        } else {
            return getS3File();
        }
    }

    /**
     * @return string
     */
    public function getGiftPriceAttribute(): string
    {
        return number_format((float) $this->price, 2, ',', '');
    }
}
