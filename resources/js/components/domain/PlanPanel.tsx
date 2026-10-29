import { FloorPlan } from '@/components/domain/FloorPlan';
import { InfoTip } from '@/components/help/InfoTip';
import { PlanEditor, type PlanEditorHandle, type PlanPayload } from '@/components/plan/PlanEditor';
import { Panel } from '@/design';
import { confirm } from '@/lib/dialogs';
import { sendJson } from '@/lib/json';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Plan, UnitType } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button, Group } from '@mantine/core';
import { IconMap, IconPencil } from '@tabler/icons-react';
import { useCallback, useEffect, useRef, useState } from 'react';

/** Laravel's CSRF token, as its cookie carries it */
/**
 * The plan: where each unit is, and what surrounds it. Admins switch it to editing in place
 * (one plan, in one place); a drawing is never lost by leaving.
 */
export function PlanPanel({
  plan,
  canEdit,
  highlightType,
  ownUnit,
  aside,
  unitTypes,
}: {
  plan: Plan;
  canEdit: boolean;
  /** For admins: units are then added on the plan too */
  unitTypes?: UnitType[];
  highlightType?: number;
  ownUnit?: number | null;
  aside?: string;
}) {
  // The plan as it was when editing began: a reload meanwhile never resets the editor
  const [editing, setEditing] = useState<Plan | null>(null);
  const editor = useRef<PlanEditorHandle>(null);

  // Saved as JSON, the page staying as it is: the server answers the ids of new shapes
  const save = useCallback(async (payload: PlanPayload) => {
    const response = await sendJson('PUT', route('manage.plan.update'), payload);
    if (!response.ok) throw new Error(`The plan was not saved (${response.status})`);
    return ((await response.json()) as { ids: Record<string, number> }).ids;
  }, []);

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
    setEditing(null);
    // The pages that show the plan read it again, as saved
    router.reload({ only: ['plan'] });
  };

  useEffect(() => {
    if (!editing) return undefined;
    const unsaved = (event: BeforeUnloadEvent) => {
      if (editor.current?.dirty()) event.preventDefault();
    };
    window.addEventListener('beforeunload', unsaved);
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
      stopGuard();
    };
  }, [editing]);

  return (
    <Panel
      title={_('Plan')}
      icon={IconMap}
      tone="accent"
      aside={editing ? undefined : aside}
      actions={
        canEdit ? (
          editing ? (
            <Group gap="xs">
              <InfoTip title={_('Drawing the plan')} tour="manage.plan">
                {_(
                  'Show people where each unit is, and what surrounds it: buildings, streets, green areas, on as many floors as needed.',
                )}
              </InfoTip>
            </Group>
          ) : (
            <Button
              variant="light"
              size="sm"
              leftSection={<IconPencil size={16} />}
              onClick={() => setEditing(plan)}
            >
              {_('Edit the plan')}
            </Button>
          )
        ) : null
      }
    >
      {editing ? (
        <PlanEditor
          ref={editor}
          plan={editing}
          save={save}
          onDone={done}
          unitTypes={unitTypes}
          onUnitAdded={() => router.reload({ only: ['unitTypes'] })}
        />
      ) : (
        <FloorPlan plan={plan} highlightType={highlightType} ownUnit={ownUnit} />
      )}
    </Panel>
  );
}
