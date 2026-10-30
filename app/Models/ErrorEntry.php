<?php

namespace App\Models;

/** One error the app ran into, counted each time it happens again */
class ErrorEntry extends Model
{
    public $timestamps = false;

    protected $table = 'error_log';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];
}
