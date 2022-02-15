<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Weboccult\EatcardCompanion\Helpers\getS3File;

class Supplement extends Model
{
    use SoftDeletes;
    protected $appends = ['user_price', 'image_url'];

    /**
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return getS3File($this->image);
        } else {
            return getS3File();
        }
    }

    /**
     * @return string
     */
    public function getUserPriceAttribute(): string
    {
        return number_format((float) $this->price, 2, ',', '');
    }
}
