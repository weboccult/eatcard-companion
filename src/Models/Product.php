<?php

namespace Weboccult\EatcardCompanion\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Weboccult\EatcardCompanion\Classes\ImageFilters;
use Weboccult\EatcardCompanion\Traits\Translatable;
use function Weboccult\EatcardCompanion\Helpers\getS3File;

class Product extends Model
{
    use Translatable;
    use SoftDeletes;

    protected $appends = ['image_url', 'original_image_url', 'user_price', 'user_regular_price', 'user_large_price', 'user_discount_price', 'discount_show', 'not_available'];

    public array $translatableFields = ['name', 'description'];

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return ImageFilters::applyFilter('ProductNormalImage', $this->image);
        } else {
            return asset('images/no_image.png');
        }
    }

    /**
     * @return bool
     */
    public function getNotAvailableAttribute(): bool
    {
        if (isset($this->available_time_setting) && $this->available_time_setting) {
            if (Carbon::now()->format('H:i') < $this->start_time || Carbon::now()->format('H:i') > $this->end_time) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getOriginalImageUrlAttribute()
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
    public function getUserRegularPriceAttribute(): string
    {
        return number_format((float) $this->regular_price, 2, ',', '');
    }

    /**
     * @return string
     */
    public function getUserLargePriceAttribute(): string
    {
        return number_format((float) $this->large_price, 2, ',', '');
    }

    /**
     * @return bool
     */
    public function getDiscountShowAttribute(): bool
    {
        if (! is_null($this->discount_price)) {
            if ($this->from_date && $this->to_date) {
                $now = Carbon::now()->format('Y-m-d');
                if (($this->from_date < $now && $this->to_date < $now) || ($this->from_date > $now && $this->to_date > $now)) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getUserDiscountPriceAttribute(): string
    {
        if (! is_null($this->discount_price)) {
            return number_format((float) $this->discount_price, 2, ',', '');
        }

        return '';
    }

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function ayce_class(): HasMany
    {
        return $this->hasMany(DineinCategoryAyce::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function printersPivot(): HasMany
    {
        return $this->hasMany(ProductPrinter::class, 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function printers(): BelongsToMany
    {
        return $this->belongsToMany(StorePrinter::class, 'product_printers', 'product_id', 'printer_id')->withPivot('id')->orderBy('pivot_id', 'asc');
    }
}
