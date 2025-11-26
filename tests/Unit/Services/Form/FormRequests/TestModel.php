<?php

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Database\Eloquent\Model;

/**
 * Dummy model for testing FormService with update operations.
 */
class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email', 'age', 'description'];
    protected $guarded = [];
}
