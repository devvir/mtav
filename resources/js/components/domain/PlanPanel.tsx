import { FloorPlan } from '@/components/domain/FloorPlan';
import { InfoTip } from '@/components/help/InfoTip';
import { PlanEditor, type PlanEditorHandle, type PlanPayload } from '@/components/plan/PlanEditor';
import { Panel } from '@/design';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Plan } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button, Group } from '@mantine/core';
import { IconMap, IconPencil } from '@tabler/icons-react';
import { useEffect, useRef, useState } from 'react';

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
}: {
  plan: Plan;
  canEdit: boolean;
  highlightType?: number;
  ownUnit?: number | null;
  aside?: string;
}) {
  const [editing, setEditing] = useState(false);
  const editor = useRef<PlanEditorHandle>(null);
  const [processing, setProcessing] = useState(false);

  const save = (payload: PlanPayload) => {
    setProcessing(true);
    router.put(route('manage.plan.update'), payload as never, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => editor.current?.markSaved(),
      onFinish: () => setProcessing(false),
    });
  };

  const done = async () => {
    if (
      editor.current?.dirty() &&
      !(await confirm({
        title: _('Leave without saving the plan?'),
        confirmLabel: _('Leave'),
        cancelLabel: _('Keep editing'),
        danger: true,
      }))
    )
      return;
    setEditing(false);
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
              onClick={() => setEditing(true)}
            >
              {_('Edit the plan')}
            </Button>
          )
        ) : null
      }
    >
      {editing ? (
        <PlanEditor ref={editor} plan={plan} processing={processing} onSave={save} onDone={done} />
      ) : (
        <FloorPlan plan={plan} highlightType={highlightType} ownUnit={ownUnit} />
      )}
    </Panel>
  );
}
