<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\Relation;

trait HasMedia
{
    public function audios(): Relation
    {
        return $this->media()->audios();
    }

    public function documents(): Relation
    {
        return $this->media()->documents();
    }

    public function images(): Relation
    {
        return $this->media()->images();
    }

    public function videos(): Relation
    {
        return $this->media()->videos();
    }

    public function visualMedia(): Relation
    {
        return $this->media()->visual();
    }
}
