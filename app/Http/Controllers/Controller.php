<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator as OnePage;
use Illuminate\Pagination\Paginator;

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

        $count = (clone $query)->count();
        if ($count <= $whole) {
            // One page holding them all, without counting them again
            return [new OnePage($query->get(), $count, max(1, $whole), 1, [
                'path' => Paginator::resolveCurrentPath(),
            ]), true];
        }

        return [$narrow($query)->paginate($perPage)->withQueryString(), false];
    }
}
