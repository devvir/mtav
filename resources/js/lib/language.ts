import { router } from '@inertiajs/react';
import { route } from './route';

/**
 * Speaking another language from now on (an account keeps it, on every device). Its words
 * come with a fresh start of the app: the language is set once, as it starts.
 */
export const changeLanguage = (
  locale: string,
  { onStart, onError }: { onStart?: () => void; onError?: () => void } = {},
) =>
  router.put(
    route('language'),
    { locale },
    { onStart, onError, onSuccess: () => window.location.reload() },
  );
