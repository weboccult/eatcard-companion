<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Weboccult\EatcardCompanion\Traits\Translatable;

class Category extends Model
{
    use Translatable;
    use SoftDeletes;

    protected $appends = ['image_url'];

    public array $translatableFields = ['name'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return getS3File($this->image);
        } else {
            return asset('images/no_image.png');
        }
    }

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
