import { sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { router } from '@inertiajs/react';
import { useDebouncedCallback } from '@mantine/hooks';
import { useEffect, useRef, useState } from 'react';

/** A move is sent this long after the last one */
const SAVE_AFTER_MS = 600;
/** "Saved" is said for this long */
const SAVED_FOR_MS = 3000;

const NOTHING: never[] = [];

export type RankingSave = 'saving' | 'saved' | null;

/**
 * A family's order as the page shows it: the server's, or else the latest move while that
 * is on its way (whatever the server sends meanwhile is older). Moves go out a moment after
 * the last one, one request at a time, and alongside a visit to another page rather than
 * cancelling it; leaving the page or closing it sends what is still waiting.
 */
export function useRankingSave<Unit extends { id: number }>(given: Unit[] | null, url: string) {
  const [moved, setMoved] = useState<Unit[] | null>(null);
  const [saved, setSaved] = useState(false);
  const unsent = useRef<{ units: Unit[]; url: string } | null>(null);
  const sending = useRef(false);

  const settle = (ok: boolean) => {
    setMoved(null);
    setSaved(ok);
  };

  const sendNext = () => {
    const next = unsent.current;
    if (!next || sending.current) return;
    unsent.current = null;
    if (heldOffline()) return settle(false);

    let ok = false;
    sending.current = true;
    router.put(
      next.url,
      { preferences: next.units.map((unit) => unit.id) },
      {
        async: true,
        preserveScroll: true,
        preserveState: true,
        only: ['ranking'],
        onSuccess: (page) => {
          ok = !page.flash.error;
        },
        onFinish: () => {
          sending.current = false;
          if (!unsent.current) settle(ok);
          else if (!send.isPending()) sendNext();
        },
      },
    );
  };

  const send = useDebouncedCallback(() => sendNext(), {
    delay: SAVE_AFTER_MS,
    flushOnUnmount: true,
  });

  useEffect(() => {
    const closing = () => {
      const next = unsent.current;
      if (!next) return;
      send.cancel();
      unsent.current = null;
      void sendJson(
        'PUT',
        next.url,
        { preferences: next.units.map((unit) => unit.id) },
        { keepalive: true },
      ).then(
        () => settle(true),
        () => settle(false),
      );
    };
    window.addEventListener('pagehide', closing);
    return () => window.removeEventListener('pagehide', closing);
  }, [send]);

  useEffect(() => {
    if (!saved) return undefined;
    const done = window.setTimeout(() => setSaved(false), SAVED_FOR_MS);
    return () => window.clearTimeout(done);
  }, [saved]);

  const rank = (units: Unit[]) => {
    unsent.current = { units, url };
    setMoved(units);
    setSaved(false);
    send();
  };

  const state: RankingSave = moved ? 'saving' : saved ? 'saved' : null;

  return { ranking: moved ?? given ?? NOTHING, rank, state };
}
