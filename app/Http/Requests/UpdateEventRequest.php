<?php

namespace App\Http\Requests;

/**
 * @property-read string $type
 * @property-read string $title
 * @property-read string $description
 * @property-read string|null $location
 * @property-read string|null $start_date
 * @property-read string|null $end_date
 * @property-read bool $is_published
 */
class UpdateEventRequest extends CreateEventRequest
{
    public function rules(): array
    {
        /**
         * Event's Create and Update Requests use the exact same rules.
         */
        return parent::rules();
    }

    /**
     * Lottery type cannot be changed (ignore updated type if provided).
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('event')->isLottery()) {
            unset($this['type']);
        }
    }
}
