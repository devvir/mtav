<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Gate;

trait HasPolicy
{
    /**
     * Build a policy class instance of this class.
     *
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getPolicy()
    {
        return Gate::getPolicyFor($this);
    }
}
