<?php

namespace Database\Factories;

use App\Models\Admin;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Admin>
 */
class AdminFactory extends UserFactory
{
    protected $model = Admin::class;

    public function configure(): static
    {
        return $this->admin();
    }
}
