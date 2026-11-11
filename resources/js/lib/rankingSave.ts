import { sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { leavesPage } from '@/lib/route';
import { router } from '@inertiajs/react';
import { useDebouncedCallback } from '@mantine/hooks';
import { useEffect, useRef, useState } from 'react';

/** A move is sent this long after the last one */
const SAVE_AFTER_MS = 600;
/** "Saved" is said for this long */
const SAVED_FOR_MS = 3000;

const NOTHING: never[] = [];

export type RankingSave = 'saving' | 'saved' | null;

/** A move to send: where, what (the ids in order, as the server takes them), what to reload */
interface Move {
  url: string;
  body: Record<string, number[]>;
  only: string[];
}

/**
 * Sent with no page to follow it, even as the page closes: saved as the server answers (its
 * yes is a redirect, not followed)
 */
const sendAsJson = ({ url, body }: Move) =>
  sendJson('PUT', url, body, { keepalive: true }).then(
    (response) => response.ok || response.type === 'opaqueredirect',
  );

/**
 * A ranking as the page shows it: the server's, or else the latest move while that is on its
 * way (whatever the server sends meanwhile is older). Moves go out a moment after the last
 * one, one request at a time, and alongside a visit to another page rather than cancelling
 * it; leaving the page (as the visit starts) or closing it sends what is still waiting. Sent
 * as `field` (the ids in order); the answer reloads `only` those props.
 */
export function useRankingSave<Unit extends { id: number }>(
  given: Unit[] | null,
  url: string,
  { field = 'preferences', only = ['ranking'] }: { field?: string; only?: string[] } = {},
) {
  const [moved, setMoved] = useState<Unit[] | null>(null);
  const [saved, setSaved] = useState(false);
  const unsent = useRef<Move | null>(null);
  const sending = useRef(false);
  // The page is gone: what still goes out goes as JSON (an answer then brings no page back).
  // Declared before the debounce, so that it is set before what waits is sent on leaving
  const left = useRef(false);
  useEffect(() => {
    left.current = false;
    return () => {
      left.current = true;
    };
  }, []);

  const settle = (ok: boolean) => {
    setMoved(null);
    setSaved(ok);
  };

  const sendNext = () => {
    const next = unsent.current;
    if (!next || sending.current) return;
    unsent.current = null;
    if (heldOffline()) return settle(false);

    sending.current = true;
    if (left.current) {
      void sendAsJson(next).then((ok) => {
        sending.current = false;
        if (unsent.current) sendNext();
        else settle(ok);
      });
      return;
    }

    let ok = false;
    router.put(next.url, next.body, {
      async: true,
      preserveScroll: true,
      preserveState: true,
      only: next.only,
      onSuccess: (page) => {
        ok = !page.flash.error;
      },
      onFinish: () => {
        sending.current = false;
        if (!unsent.current) settle(ok);
        else if (!send.isPending()) sendNext();
      },
    });
  };

  const send = useDebouncedCallback(() => sendNext(), {
    delay: SAVE_AFTER_MS,
    flushOnUnmount: true,
  });

  // Another page, the moment the visit starts: what waits goes out while this page is still
  // the one in sight (its answer then never brings this page back)
  useEffect(
    () =>
      router.on('before', (event) => {
        if (unsent.current && send.isPending() && leavesPage(event.detail.visit)) send.flush();
      }),
    [send],
  );

  useEffect(() => {
    const closing = () => {
      const next = unsent.current;
      if (!next) return;
      send.cancel();
      unsent.current = null;
      void sendAsJson(next).then(settle);
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
    unsent.current = { url, body: { [field]: units.map((unit) => unit.id) }, only };
    setMoved(units);
    setSaved(false);
    send();
  };

  const state: RankingSave = moved ? 'saving' : saved ? 'saved' : null;

  return { ranking: moved ?? given ?? NOTHING, rank, state };
}
