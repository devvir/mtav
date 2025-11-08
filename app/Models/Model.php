<?php

namespace App\Models;

use App\Models\Concerns\HasPolicy;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends EloquentModel
{
    use ConvertsToJsonResource;
    use HasFactory;
    use HasPolicy;
    use SoftDeletes;

    protected $guarded = ['id'];
}
