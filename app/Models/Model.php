<?php

namespace App\Models;

use App\Models\Concerns\DerivedRelations;
use App\Models\Concerns\HasPolicy;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    use ConvertsToJsonResource;
    use DerivedRelations;
    use HasFactory;
    use HasPolicy;

    protected $guarded = ['id'];
}
