<?php

namespace App\Services\Form\Lib;

use Illuminate\Database\Eloquent\Model;

class SpecFactory
{
    public static function make(Rule $rule, ?Model $model = null): Spec
    {
        $specClass = self::determineSpecType($rule);

        return new $specClass($rule, $model);
    }

    protected static function determineSpecType(Rule $rule): string
    {
        return static::isSelect($rule) ? SpecSelect::class : SpecInput::class;
    }

    protected static function isSelect(Rule $rule): bool
    {
        return  $rule->type === 'boolean'                                   // Boolean fields (on/off select)
            || isset($rule->in) || isset($rule->enum)                       // Enum or hardcoded value lists
            || $rule->type === 'array'                                      // Multi-select fields (array fields)
            || isset($rule->exists)                                         // FK-based relations (db lookups)
            || isset($rule->customRules['App\\Rules\\BelongsToProject']);   // Project-scoped relation
    }
}
