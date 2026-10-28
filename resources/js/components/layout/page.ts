import { setRoutes } from '@/lib/route';
import { toast, type ToastTone } from '@/lib/toasts';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * The visit being shown. Layouts call it first, so route() knows this visit's routes (and
 * the project they default to) before anything renders.
 */
export function useCurrentPage() {
  const page = usePage();
  setRoutes(page.props.routes, page.props.ziggy);

  return page;
}

/** Each visit's flash messages, as toasts */
export function useFlashToasts() {
  const { flash } = usePage().props;

  useEffect(() => {
    (['success', 'info', 'warning', 'error'] as ToastTone[]).forEach(
      (tone) => flash?.[tone] && toast({ tone, message: flash[tone] as string }),
    );
  }, [flash]);
}
