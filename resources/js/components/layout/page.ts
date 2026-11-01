import { setRoutes } from '@/lib/route';
import { toast, type ToastTone } from '@/lib/toasts';
import { router, usePage } from '@inertiajs/react';

/**
 * The visit being shown. Layouts call it first, so route() knows this visit's routes (and
 * the project they default to) before anything renders.
 */
export function useCurrentPage() {
  const page = usePage();
  setRoutes(page.props.routes, page.props.ziggy);

  return page;
}

/** The server's messages as toasts, each as it comes: a page brought back by Back has none */
export const showFlashToasts = () =>
  router.on('flash', ({ detail: { flash } }) =>
    (['success', 'info', 'warning', 'error'] as ToastTone[]).forEach(
      (tone) => flash[tone] && toast({ tone, message: flash[tone] }),
    ),
  );
