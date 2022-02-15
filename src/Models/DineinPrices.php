<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Weboccult\EatcardCompanion\Helpers\getS3File;

class DineinPrices extends Model
{
    use SoftDeletes;

    protected $appends = [
        'image_url',
        'user_price',
        'user_child_price',
        'user_child_price_2',
    ];

    /**
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return getS3File($this->image);
        } else {
            return asset('images/no_image.png');
        }
    }

    /**
     * @return string
     */
    public function getUserPriceAttribute(): string
    {
        return number_format((float) $this->price, 2, ',', '');
    }

    /**
     * @return string
     */
    public function getUserChildPriceAttribute(): string
    {
        return number_format((float) $this->child_price, 2, ',', '');
    }

    /**
     * @return string
     */
    public function getUserChildPrice2Attribute(): string
    {
        return number_format((float) $this->child_price_2, 2, ',', '');
    }

    /**
     * @return BelongsTo
     */
    public function dineInCategory(): BelongsTo
    {
        return $this->belongsTo(DineinPriceCategory::class, 'dinein_category_id');
    }

    /**
     * @return HasMany
     */
    public function dynamicPrices(): HasMany
    {
        return $this->hasMany(DineinPriceClassChildPrice::class, 'dinein_price_id');
    }

    /**
     * @return BelongsTo
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_type');
    }
}
