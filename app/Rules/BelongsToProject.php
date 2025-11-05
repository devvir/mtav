<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BelongsToProject implements ValidationRule
{
    public function __construct(
        protected string $modelClass,
        protected ?int $projectId,
        protected string $translationKey = 'validation.belongs_to_project'
    ) {
        // ...
    }

    public function validate(string $_, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        if ($this->projectId === null) {
            $fail(__($this->translationKey));

            return;
        }

        $modelBelongsToProject = $this->modelClass::where([
            'id' => $value,
            'project_id' => $this->projectId,
        ])->exists();

        if (! $modelBelongsToProject) {
            $fail(__($this->translationKey));
        }
    }
}
