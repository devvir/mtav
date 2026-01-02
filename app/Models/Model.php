<?php

namespace App\Models;

use App\Models\Concerns\DerivedRelations;
use App\Models\Concerns\ExtendedRelations;
use App\Models\Concerns\HasPolicy;
use App\Observers\ModelObserver;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

#[ObservedBy(ModelObserver::class)]
class Model extends EloquentModel
{
    use ConvertsToJsonResource;
    use DerivedRelations;
    use ExtendedRelations;
    use HasFactory;
    use HasPolicy;

    protected $guarded = ['id'];
}
