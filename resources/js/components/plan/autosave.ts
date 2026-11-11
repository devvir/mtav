import { fieldErrors } from '@/lib/json';
import { _ } from '@/lib/translations';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { PlanModel } from './model';

export type SaveStatus = 'saved' | 'unsaved' | 'saving' | 'failed';

/** A save the server refused: said as it is (an empty message says nothing more), never sent again as it was */
export class SaveRefused extends Error {}

/** How long after the last change the plan saves itself (changes made meanwhile go along) */
const AUTOSAVE_MS = 1200;
/** How long after a save that could not reach the server it is tried again */
const RETRY_MS = 5000;

/**
 * Why a save failed, from the server's answer: one that cannot answer now (or no connection,
 * which `sendJson` answers as 503) is tried again; anything else is refused, and said
 */
export async function notSaved(response: Response): Promise<Error> {
  if (response.status === 503) return new Error('The server cannot answer now: tried again');
  if (response.status === 401 || response.status === 419)
    return new SaveRefused(_('You were signed out. Sign in again.'));
  if (response.status === 422) {
    const errors = await fieldErrors(response).catch(() => ({}) as Record<string, string>);
    return new SaveRefused(Object.values(errors)[0] ?? '');
  }
  return new SaveRefused('');
}

/**
 * The plan saving itself a moment after each change, one save at a time: a failure to reach
 * the server is tried again every few seconds; one the server refused waits for a new change
 * (or Try again), and says why (`reason`)
 */
export function useAutosave(
  model: PlanModel,
  version: number,
  save: (payload: ReturnType<PlanModel['payload']>) => Promise<Record<string, number>>,
) {
  const [sending, setSending] = useState<'saving' | 'failed' | null>(null);
  // What was refused, and why: that state is not sent again by itself
  const [refusal, setRefusal] = useState<{ state: string; reason: string } | null>(null);
  const saving = useRef<Promise<boolean> | null>(null);

  const flush = useCallback(async (): Promise<boolean> => {
    if (saving.current) await saving.current;
    if (!model.dirty) return true;
    const sent = model.state;
    setSending('saving');
    saving.current = save(model.payload())
      .then((ids) => {
        model.markSaved(sent, ids);
        setSending(null);
        setRefusal(null);
        return !model.dirty;
      })
      .catch((error: unknown) => {
        setSending('failed');
        setRefusal(error instanceof SaveRefused ? { state: sent, reason: error.message } : null);
        return false;
      })
      .finally(() => {
        saving.current = null;
      });
    return saving.current;
  }, [model, save]);

  const failed = sending === 'failed';
  const waitsForChange = refusal !== null && refusal.state === model.state;
  useEffect(() => {
    if (!model.dirty || waitsForChange) return undefined;
    const timer = window.setTimeout(
      () => void flush(),
      failed && !refusal ? RETRY_MS : AUTOSAVE_MS,
    );
    return () => window.clearTimeout(timer);
  }, [version, flush, failed, refusal, waitsForChange, model]);

  const status: SaveStatus = sending ?? (model.dirty ? 'unsaved' : 'saved');

  return { status, reason: failed ? (refusal?.reason ?? null) : null, flush };
}
