import { FloorPlan } from '@/components/domain/FloorPlan';
import { usePlanFullScreen } from '@/components/domain/PlanFullScreen';
import { InfoTip } from '@/components/help/InfoTip';
import { useIdle } from '@/components/plan/idle';
import {
  PlanEditor,
  SaveState,
  type PlanEditorHandle,
  type PlanPayload,
  type SaveStatus,
} from '@/components/plan/PlanEditor';
import { Panel } from '@/design';
import { confirm } from '@/lib/dialogs';
import { sendJson } from '@/lib/json';
import { connection, heldOffline } from '@/lib/offline';
import { route, routeIs } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { _ } from '@/lib/translations';
import type { Plan, UnitType } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button, Group, Modal, Text } from '@mantine/core';
import { useMediaQuery } from '@mantine/hooks';
import { IconCheck, IconMap, IconPencil } from '@tabler/icons-react';
import { useCallback, useEffect, useRef, useState } from 'react';

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

/** An editor left alone this long is asked whether anyone is still drawing… */
const IDLE_WARN_MS = 10 * 60_000;
/** …and closes this long after (what was drawn saved), so that others may draw */
const IDLE_CLOSE_MS = 12 * 60_000;

/**
 * The plan: where each unit is, and what surrounds it. Admins switch it to editing in place
 * (one plan, in one place), and back where they began, by its title; a drawing is never lost
 * by leaving. One person draws it at a time: anyone else is told who is. The hold ends with
 * "Done editing", by itself soon after the window closes or loses its connection, and when
 * the editor is left alone too long (warned first, then closed, its drawing saved). Drawing
 * takes a large screen and a precise pointer (a phone reads the plan, full screen and
 * zoomed, but does not draw it). It can take the whole window.
 */
export function PlanPanel({
  plan,
  canEdit,
  highlightType,
  ownUnit,
  aside,
  unitTypes,
  editAtOnce,
}: {
  plan: Plan;
  canEdit: boolean;
  /** Reached at its own address (units/edit): drawing from the start */
  editAtOnce?: boolean;
  /** For admins: units are then added on the plan too */
  unitTypes?: UnitType[];
  highlightType?: number;
  ownUnit?: number | null;
  aside?: string;
}) {
  // The plan as it was when editing began: a reload meanwhile never resets the editor
  const [editing, setEditing] = useState<Plan | null>(null);
  const editor = useRef<PlanEditorHandle>(null);
  const [status, setStatus] = useState<SaveStatus>('saved');
  const fullScreen = usePlanFullScreen();
  const canDraw = useMediaQuery('(min-width: 62em) and (pointer: fine)', true, {
    getInitialValueInEffect: false,
  });

  // Saved as JSON, the page staying as it is: the server answers the ids of new shapes
  const save = useCallback(async (payload: PlanPayload) => {
    // Offline it waits, quietly (the editor says it is not saved yet), for the connection
    if (!connection.get().online) throw new Error('Offline: the plan waits for the connection');
    const response = await sendJson('PUT', route('manage.plan.update'), payload);
    if (response.status === 423) await taken(response);
    if (!response.ok) throw new Error(`The plan was not saved (${response.status})`);
    return ((await response.json()) as { ids: Record<string, number> }).ids;
  }, []);

  const start = async () => {
    if (heldOffline()) return;
    const response = await sendJson('POST', route('manage.plan.lock'), {});
    if (response.status === 423) return taken(response);
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

  // Its own address opens it for drawing, in view (where drawing can be: a large screen)
  const opened = useRef(false);
  useEffect(() => {
    if (!editAtOnce || !canEdit || !canDraw || opened.current) return;
    opened.current = true;
    void start().then(() =>
      document.querySelector('.plan-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
    );
  });

  const done = async () => {
    // What is pending goes first; only what could not be saved asks
    const saved = (await editor.current?.flush()) ?? true;
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

  // Left alone: warned, then closed once what was drawn is saved (never before)
  const closesIn = useIdle(Boolean(editing), {
    warnAfter: IDLE_WARN_MS,
    closeAfter: IDLE_CLOSE_MS,
    onClose: async () => {
      if (!((await editor.current?.flush()) ?? true)) return false;
      leave();
      toast({
        tone: 'info',
        message: _('The plan closed after a while with nothing drawn; what you drew is saved.'),
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
          response.status === 423 ? taken(response) : undefined,
        ),
      30_000,
    );
    const letGo = () =>
      void sendJson('DELETE', route('manage.plan.unlock'), {}, { keepalive: true });
    window.addEventListener('pagehide', letGo);
    const stopGuard = router.on('before', (event) => {
      if (
        editor.current?.dirty() &&
        event.detail.visit.method === 'get' &&
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
  }, [editing]);

  return (
    <Panel
      title={_('Plan')}
      icon={IconMap}
      tone="accent"
      className="plan-panel"
      {...fullScreen.panel}
      aside={editing ? undefined : aside}
      actions={
        <Group gap="xs" wrap="nowrap">
          {editing ? (
            <>
              <InfoTip title={_('Drawing the plan')} tour="manage.plan">
                {_(
                  'Show people where each unit is, and what surrounds it: buildings, streets, green areas, on as many floors as needed.',
                )}
              </InfoTip>
              <Group gap="md" wrap="nowrap" data-tour="plan.save">
                <SaveState status={status} onRetry={() => void editor.current?.flush()} />
                <Button
                  variant="light"
                  size="sm"
                  leftSection={<IconCheck size={16} />}
                  onClick={done}
                >
                  {_('Done editing')}
                </Button>
              </Group>
            </>
          ) : canEdit && canDraw ? (
            <Button
              variant="light"
              size="sm"
              leftSection={<IconPencil size={16} />}
              onClick={() => void start()}
            >
              {_('Edit the plan')}
            </Button>
          ) : null}
          {fullScreen.button}
        </Group>
      }
    >
      {editing ? (
        <PlanEditor
          ref={editor}
          plan={editing}
          save={save}
          onStatus={setStatus}
          unitTypes={unitTypes}
          onUnitAdded={() => router.reload({ only: ['unitTypes'] })}
        />
      ) : (
        <FloorPlan plan={plan} highlightType={highlightType} ownUnit={ownUnit} />
      )}
      <Modal
        opened={closesIn !== null}
        onClose={() => undefined}
        withCloseButton={false}
        title={_('Still drawing the plan?')}
        centered
      >
        <Text>
          {_(
            'The editor closes in {seconds} seconds, saving what you drew, so that others can draw the plan.',
            { seconds: closesIn ?? 0 },
          )}
        </Text>
        <Group justify="flex-end" mt="lg">
          {/* Any touch keeps it open: this is the obvious one */}
          <Button data-autofocus>{_('Keep drawing')}</Button>
        </Group>
      </Modal>
    </Panel>
  );
}
