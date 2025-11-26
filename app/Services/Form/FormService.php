<?php

namespace App\Services\Form;

use Illuminate\Database\Eloquent\Model;

class FormService
{
    public static function make(Model|string $model, FormType $type)
    {
        $provider = new DataProvider($model, $type);

        return new Form($provider);
    }
}
