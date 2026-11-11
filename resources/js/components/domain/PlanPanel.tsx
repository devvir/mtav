import { FloorPlan } from '@/components/domain/FloorPlan';
import { usePlanFullScreen } from '@/components/domain/PlanFullScreen';
import { InfoTip } from '@/components/help/InfoTip';
import type { SaveStatus } from '@/components/plan/autosave';
import { IdleWarning } from '@/components/plan/IdleWarning';
import { PlanEditor } from '@/components/plan/PlanEditor';
import { SaveState } from '@/components/plan/SaveState';
import { useEditingSession } from '@/components/plan/session';
import { Panel } from '@/design';
import { scrollBehavior } from '@/lib/motion';
import { _ } from '@/lib/translations';
import type { Plan, UnitType } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button, Group } from '@mantine/core';
import { useMediaQuery } from '@mantine/hooks';
import { IconCheck, IconMap, IconPencil } from '@tabler/icons-react';
import { useCallback, useEffect, useRef, useState } from 'react';

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
  // The hold on the plan, what the editor draws on and how it is saved
  const { editing, editor, save, start, done, retry, closesIn } = useEditingSession(plan);
  const [saving, setSaving] = useState<{ status: SaveStatus; reason: string | null }>({
    status: 'saved',
    reason: null,
  });
  const onStatus = useCallback(
    (status: SaveStatus, reason: string | null) => setSaving({ status, reason }),
    [],
  );
  const fullScreen = usePlanFullScreen();
  const canDraw = useMediaQuery('(min-width: 62em) and (pointer: fine)', true, {
    getInitialValueInEffect: false,
  });

  // Its own address opens it for drawing, in view (where drawing can be: a large screen)
  const opened = useRef(false);
  useEffect(() => {
    if (!editAtOnce || !canEdit || !canDraw || opened.current) return;
    opened.current = true;
    void start().then(() =>
      document
        .querySelector('.plan-panel')
        ?.scrollIntoView({ behavior: scrollBehavior(), block: 'start' }),
    );
  });

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
                <SaveState status={saving.status} reason={saving.reason} onRetry={retry} />
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
          onStatus={onStatus}
          unitTypes={unitTypes}
          onUnitAdded={() => router.reload({ only: ['unitTypes'] })}
        />
      ) : (
        <FloorPlan plan={plan} highlightType={highlightType} ownUnit={ownUnit} />
      )}
      <IdleWarning closesIn={closesIn} />
    </Panel>
  );
}
