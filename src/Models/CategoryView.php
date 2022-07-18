<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Weboccult\EatcardCompanion\Traits\Translatable;
use function Weboccult\EatcardCompanion\Helpers\getS3File;

class CategoryView extends Model
{
    use Translatable;

    protected $appends = ['image_url'];

    public $table = 'categories';

    public array $translatableFields = ['name'];

    /**
     * @return mixed
     */
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
        return $this->hasMany(ProductView::class, 'category_id');
    }
}
