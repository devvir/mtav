<?php

namespace App\Http\Requests;

class UpdateEventRequest extends CreateEventRequest
{
    public function rules(): array
    {
        /**
         * Event's Create and Update Requests use the exact same rules.
         *
         * @see CreateEventRequest
         */
        return parent::rules();
    }

    /**
     * Lottery type cannot be changed (ignore updated type if provided).
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('event')->isLottery()) {
            $this->merge(['type' => $this->route('event')->type]);
        }
    }
}
