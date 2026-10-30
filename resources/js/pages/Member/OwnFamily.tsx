import { FamilyForm } from '@/components/forms/FamilyForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Ref } from '@/types/models';
import { router, usePage } from '@inertiajs/react';
import { Button, Card, Text } from '@mantine/core';

/**
 * Let into a project without guests: creating their family comes first, unless someone of it
 * adds them. All there is for them until then, and the way back out.
 */
export default function OwnFamily({ types }: { types: Ref[] | null }) {
  const project = usePage().props.project!;

  const leave = async () => {
    const ok = await confirm({
      title: _('Leave {project}?', { project: project.name }),
      confirmLabel: _('Leave'),
      danger: true,
    });
    if (ok) router.delete(route('membership.leave', { project: project.id }));
  };

  return (
    <>
      <PageHeader
        title={_('Welcome to {project}', { project: project.name })}
        description={_(
          'Everyone here takes part as a family. Create yours; if it is already here, ask someone in it to add you with your email.',
        )}
      />
      <Card p="lg" maw={560}>
        {types?.length ? (
          <FamilyForm own unitTypes={types} onDone={() => undefined} />
        ) : (
          <Text c="dimmed">
            {_(
              'Families cannot be created right now. Someone in yours can add you with your email.',
            )}
          </Text>
        )}
      </Card>
      <Button variant="subtle" color="gray" mt="md" onClick={leave}>
        {_('Leave the project')}
      </Button>
    </>
  );
}
