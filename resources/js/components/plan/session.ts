import { confirm } from '@/lib/dialogs';
import { sendJson } from '@/lib/json';
import { connection, heldOffline } from '@/lib/offline';
import { leavesPage, route, routeIs } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { _ } from '@/lib/translations';
import type { Plan } from '@/types/models';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { notSaved } from './autosave';
import { useIdle } from './idle';
import type { PlanEditorHandle, PlanPayload } from './PlanEditor';

/** An editor left alone this long is asked whether anyone is still drawing… */
const IDLE_WARN_MS = 10 * 60_000;
/** …and closes this long after (what was drawn saved), so that others may draw */
const IDLE_CLOSE_MS = 12 * 60_000;
/** How often the hold on the plan is renewed while drawing */
const RENEW_MS = 30_000;

/** Someone else is drawing the plan: said, rather than saving over them */
const taken = async (response: Response) => {
  const { editor } = (await response.json()) as { editor: string };
  toast({
    tone: 'warning',
    message: _('{name} is drawing the plan right now. Try again in a little while.', {
      name: editor,
    }),
  });
};

/**
 * One person drawing the plan at a time: `start` takes the hold on it (the server's editing
 * lock; anyone else is told who is drawing) and `editing` is then the plan as it was, for the
 * editor (`editor`, its handle) to draw on; `save` sends what it draws. The hold is renewed
 * while the editor is open and let go with `done` (what is pending saved first; only what
 * could not be saved asks), by itself as the page closes, and when the editor is left alone
 * too long: warned first (`closesIn`, the seconds left), then closed, its drawing saved.
 * Leaving the page with something unsaved asks first. A hold that ran out and was taken by
 * someone else closes the editor, said once.
 */
export function useEditingSession(plan: Plan) {
  // The plan as it was when editing began: a reload meanwhile never resets the editor
  const [editing, setEditing] = useState<Plan | null>(null);
  const editor = useRef<PlanEditorHandle>(null);

  // This hold ran out and someone else holds it now: nothing more can be saved, so the
  // editor closes, said once
  const lost = useRef(false);
  const lose = useCallback(async (response: Response) => {
    if (lost.current) return;
    lost.current = true;
    setEditing(null);
    router.reload({ only: ['plan'] });
    await taken(response);
  }, []);

  // Saved as JSON, the page staying as it is: the server answers the ids of new shapes. What
  // it refuses is said, and waits for a change; what never reached it is tried again
  const save = useCallback(
    async (payload: PlanPayload) => {
      // Offline it waits, quietly (the editor says it is not saved yet), for the connection
      if (!connection.get().online) throw new Error('Offline: the plan waits for the connection');
      const response = await sendJson('PUT', route('manage.plan.update'), payload);
      if (response.status === 423) await lose(response);
      if (!response.ok) throw await notSaved(response);
      return ((await response.json()) as { ids: Record<string, number> }).ids;
    },
    [lose],
  );

  const start = async () => {
    if (heldOffline()) return;
    const response = await sendJson('POST', route('manage.plan.lock'), {});
    if (response.status === 423) return taken(response);
    if (response.status === 401 || response.status === 419)
      return toast({ tone: 'error', message: _('You were signed out. Sign in again.') });
    if (!response.ok) return toast({ tone: 'error', message: _("That didn't work. Try again.") });
    lost.current = false;
    setEditing(plan);
  };

  const leave = () => {
    setEditing(null);
    void sendJson('DELETE', route('manage.plan.unlock'), {});
    // The pages that show the plan read it again, as saved; done at the editor's own
    // address, back to the plain one
    if (routeIs('units.edit'))
      router.visit(route('units.index'), { replace: true, preserveScroll: true });
    else router.reload({ only: ['plan'] });
  };

  const done = async () => {
    // What is pending goes first; only what could not be saved asks
    const saved = (await editor.current?.flush()) ?? true;
    if (lost.current) return;
    if (
      !saved &&
      !(await confirm({
        title: _('Leave without saving the plan?'),
        confirmLabel: _('Leave'),
        cancelLabel: _('Keep editing'),
        danger: true,
      }))
    )
      return;
    leave();
  };

  /** Saving tried again now (the editor's own retries wait a few seconds) */
  const retry = () => void editor.current?.flush();

  // Left alone: warned, then closed once what was drawn is saved (never before)
  const closesIn = useIdle(Boolean(editing), {
    warnAfter: IDLE_WARN_MS,
    closeAfter: IDLE_CLOSE_MS,
    onClose: async () => {
      if (!((await editor.current?.flush()) ?? true)) return false;
      leave();
      toast({
        tone: 'info',
        message: _('The plan closed after a while with no changes. What you drew is saved.'),
      });
      return true;
    },
  });

  useEffect(() => {
    if (!editing) return undefined;
    const unsaved = (event: BeforeUnloadEvent) => {
      if (editor.current?.dirty()) event.preventDefault();
    };
    window.addEventListener('beforeunload', unsaved);
    // Still drawing: the hold renewed every half minute, let go as the page closes
    const renew = window.setInterval(
      () =>
        void sendJson('POST', route('manage.plan.lock'), {}).then((response) =>
          response.status === 423 ? lose(response) : undefined,
        ),
      RENEW_MS,
    );
    const letGo = () =>
      void sendJson('DELETE', route('manage.plan.unlock'), {}, { keepalive: true });
    window.addEventListener('pagehide', letGo);
    const stopGuard = router.on('before', (event) => {
      if (
        editor.current?.dirty() &&
        leavesPage(event.detail.visit) &&
        !window.confirm(_('Leave without saving the plan?'))
      ) {
        event.preventDefault();
      }
    });
    return () => {
      window.removeEventListener('beforeunload', unsaved);
      window.clearInterval(renew);
      window.removeEventListener('pagehide', letGo);
      stopGuard();
    };
  }, [editing, lose]);

  return { editing, editor, save, start, done, retry, closesIn };
}
