import { router } from '@inertiajs/react';
import { connection } from './offline';

/**
 * Details shown over a list (a person over the people, …), with the item in the address
 * (?person=19): opening and closing are visits that reload only that prop, so a link opens
 * it, and the browser's back button closes it. Without the server, they still close: here,
 * the address following.
 */
export function useDetailsInAddress(param: string, alongside: string[] = []) {
  const visit = (id: number | null, replace = false) => {
    const url = new URL(window.location.href);
    if (id === null) url.searchParams.delete(param);
    else url.searchParams.set(param, String(id));
    const address = url.pathname + url.search;

    const closeHere = () =>
      router.replace({
        url: address,
        props: (props) => ({ ...props, [param]: null }),
        preserveState: true,
        preserveScroll: true,
      });
    if (id === null && !connection.get().online) return closeHere();

    return router.get(
      address,
      {},
      {
        preserveState: true,
        preserveScroll: true,
        only: [param, ...alongside],
        replace,
        // The server out of reach, found only now
        onNetworkError: () => {
          if (id === null) closeHere();
        },
      },
    );
  };

  return {
    open: (id: number) => visit(id),
    // From one item to the next (a carousel's): Back still leaves the details at once
    move: (id: number) => visit(id, true),
    close: () => visit(null),
  };
}
