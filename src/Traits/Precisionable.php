<?php

namespace Weboccult\EatcardCompanion\Traits;

use Illuminate\Support\Facades\Log;

/**
 * @description To handle precision for database fields
 */
trait Precisionable
{
	use Splitable;

	public static function bootPrecisionable()
	{
		static::saving(function ($model) {
			try {
				if (isset($model->precisions) && !empty($model->precisions)) {
					foreach ($model->precisions as $field => $precisions) {

						//check field not have any attribute then set value as field
						if (!is_array($precisions)) {
							$field = $precisions;
						}
						// check field ids exits on model or not
						if ($model->hasAttribute($field) && in_array($field, $model->fillable)) {
							$precision = 4;
							$difference_field = null;
							$difference_precision = 4;
							$payment_digit = 2;
							$skip_reguler_split = false;
							$skip_reguler_split_fields = [
								'total_price',
								'cash_paid'
							];
							//Check for any specific precision is declared or not
							if (isset($precisions['precision']) && !empty($precisions['precision'])) {
								$precision = (int)$precisions['precision'];
							}
							//skip save difference calc and fixe split with two digits for below fields for cash payment
							if (isset($model->method) && $model->method == 'cash' && in_array($field, $skip_reguler_split_fields)) {
								$skip_reguler_split = true;
								$precision = 2;
							}
							//Check for any specific difference_field is declared or not | skip for cash payment methods
							if (!$skip_reguler_split && isset($precisions['difference']) && !empty($precisions['difference'])) {
								$difference_field = $precisions['difference'];
								if ($model->hasAttribute($difference_field) && in_array($difference_field, $model->fillable)) {
									if (isset($precisions['difference_precision']) && !empty($precisions['difference_precision'])) {
										$difference_precision = (int)$precisions['difference_precision'];
									}
									if (isset($precisions['payment_digit']) && !empty($precisions['payment_digit'])) {
										$payment_digit = (int)$precisions['payment_digit'];
									}
									$model->$difference_field = $model->splitDigits($model->$field, $precision, true, $difference_precision, $payment_digit);
								}
							}
							$model->$field = $model->splitDigits($model->$field, $precision);
						}
					}
				}
			}
			catch (\Exception $e) {
				Log::info('applySplitdigit error: ' . $e->getMessage() . '-' . $e->getLine() . '-' . $e->getFile());
			}
		});
	}

	/**
	 * @param $attr
	 * @return bool
	 */
	public function hasAttribute($attr): bool
	{
		return array_key_exists($attr, $this->attributes);
	}

}
