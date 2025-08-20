<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Str;

abstract class ResourceController extends RoutingController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $model = preg_replace('/Controller$/', '', class_basename(static::class));
        $parameter = Str::snake($model);

        $this->authorizeResource("App\\Models\\$model", $parameter);
    }
}
