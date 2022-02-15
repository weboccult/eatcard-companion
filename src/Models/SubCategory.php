<?php

namespace Weboccult\EatcardCompanion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Weboccult\EatcardCompanion\Traits\Translatable;

class SubCategory extends Model
{
    use Translatable;

    protected $fillable = [
        'store_id',
        'user_id',
        'name',
        'is_multi_select',
        'is_required',
        'max_select',
        'is_free',
        'selected_by_default',
        'allow_quantity',
        'display_deselected',
    ];

    public $translatableFields = ['name'];

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Supplement::class, 'sub_category_supplements', 'sub_category_id', 'supplement_id')
            ->withPivot('id');
    }
}
