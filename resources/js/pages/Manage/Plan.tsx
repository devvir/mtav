import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { PlanEditor, type PlanEditorHandle, type PlanPayload } from '@/components/plan/PlanEditor';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Plan as PlanData } from '@/types/models';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Plan({ plan }: { plan: PlanData }) {
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

  /** A drawing is never lost by leaving the page */
  useEffect(() => {
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
  }, []);

  return (
    <>
      <PageHeader
        title={_('Plan')}
        description={_(
          'Where each unit is, and what surrounds it. Everyone in the project sees it.',
        )}
        help={
          <InfoTip title={_('Drawing the plan')} tour="manage.plan">
            {_(
              'Show people where each unit is, and what surrounds it: buildings, streets, green areas, on as many floors as needed.',
            )}
          </InfoTip>
        }
      />
      <PlanEditor ref={editor} plan={plan} processing={processing} onSave={save} />
    </>
  );
}
