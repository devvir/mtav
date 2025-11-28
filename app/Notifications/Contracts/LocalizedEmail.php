<?php

namespace App\Notifications\Contracts;

use Illuminate\Foundation\Auth\User;

interface LocalizedEmail
{
    /**
     * Get the email template name (without locale prefix).
     * e.g. 'reset-password' for emails.{locale}.reset-password
     */
    public function template(): string;

    /**
     * Get the translation key for the email subject.
     * e.g. 'reset_password' for __('emails.reset_password')
     */
    public function subjectKey(User $notifiable): string;

    /**
     * Get additional data to pass to the email template.
     *
     * @return array<string, mixed>
     */
    public function data(User $notifiable): array;
}
