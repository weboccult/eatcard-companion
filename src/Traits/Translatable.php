<?php

namespace Weboccult\EatcardCompanion\Traits;

use App\Models\Translate;

trait Translatable
{
    public static function transLangUpdateDB($requestLang, $model, $class)
    {
        try {
            $langData = [];
            $sysLangs = config('app.supported_locales');
            foreach ($requestLang as $field_name => $value) {
                $data = [];
                foreach ($sysLangs as $code => $name) {
                    $data['field_name'] = $field_name;
                    $data['locale'] = $code;
                    if (is_string(request()->get($field_name)) || $field_name == 'name') {
                        $data['text'] = isset($value[$code]) ? $value[$code] : request()->get($field_name);
                    } else {
                        $data['text'] = '';
                    }
                    $data['translatable_id'] = $model->id;
                    $data['translatable_type'] = $class;
                    $langData[] = $data;
                }
            }
            $model->translates()->delete();
            if (collect($langData)->count()) {
                $model->translates()->insert($langData);
            }
        } catch (\Exception $e) {
            //			dd($e->getMessage());
        }
    }

    public function translates()
    {
        return $this->morphMany(Translate::class, 'translatable');
    }

    public function translateByLocale()
    {
        $currentLocale = \App::getLocale();

        return $this->morphMany(Translate::class, 'translatable')->where('locale', $currentLocale);
    }

    public function translateByField($field, $locale = null)
    {
        if (! in_array($field, $this->translatableFields)) {
            return $field;
        }

        $current_locale = \App::getLocale();
        if ($locale) {
            $current_locale = $locale;
        }

        if (! isset($this->translates)) {
            $this->load('translates');
        }

        if (isset($this->translates) && collect($this->translates)->count()) {
            $field_data = collect($this->translates)->where('locale', $current_locale)->where('field_name', $field)->first();
        } else {
            $field_data = $this->translates()->where('locale', $current_locale)->where('field_name', $field)->first();
        }

        if (collect($field_data)->count() && $field_data->text != '') {
            return $field_data->text;
        } else {
            if (isset($this->{$field})) {
                return $this->{$field};
            }
        }
    }
}
