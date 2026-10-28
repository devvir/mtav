<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * A list, whole when it is short (config lists.whole: the browser narrows it, with no
     * request per keystroke), else a page of it narrowed here.
     *
     * @param Closure(Builder|Relation): (Builder|Relation) $narrow  The list's search and filters
     *
     * @return array{0: LengthAwarePaginator, 1: bool}  The items, and whether they are all of them
     */
    protected function wholeOrPaged(Builder|Relation $query, Closure $narrow, int $perPage): array
    {
        $whole = config('lists.whole');

        if ((clone $query)->count() <= $whole) {
            return [$query->paginate(max(1, $whole)), true];
        }

        return [$narrow($query)->paginate($perPage)->withQueryString(), false];
    }
}
