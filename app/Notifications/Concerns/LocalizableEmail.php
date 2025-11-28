<?php

namespace App\Notifications\Concerns;

use App\Notifications\Contracts\LocalizedEmail;
use Illuminate\Notifications\Messages\MailMessage;

trait LocalizableEmail
{
    /**
     * Build the mail message with locale support and fallback.
     *
     * Requires the class to implement LocalizedEmail interface.
     */
    public function toMail($notifiable): MailMessage
    {
        if (! $this instanceof LocalizedEmail) {
            throw new \LogicException(
                'Classes using LocalizableEmail trait must implement LocalizedEmail interface'
            );
        }

        $locale = app()->getLocale();
        $template = $this->template();
        $view = "emails.{$locale}.{$template}";

        // Fallback to English if locale template doesn't exist
        if (! view()->exists($view)) {
            $view = "emails.en.{$template}";
        }

        $data = [
            'user' => $notifiable,
            ...$this->data($notifiable),
        ];

        $subjectKey = $this->subjectKey($notifiable);

        return (new MailMessage())
            ->subject(__("emails.{$subjectKey}"))
            ->view($view, $data);
    }
}
