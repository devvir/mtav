import { QuerySearch } from '@/components/feedback/QuerySearch';
import { FamilyForm } from '@/components/forms/FamilyForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { FamilyMark } from '@/design';
import { confirm } from '@/lib/dialogs';
import { matches } from '@/lib/localList';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Ref } from '@/types/models';
import { router, usePage } from '@inertiajs/react';
import { Alert, Badge, Button, Card, Group, Modal, SimpleGrid, Stack, Text } from '@mantine/core';
import { useState } from 'react';

interface FamilyOption {
  id: number;
  name: string;
  unit_type: string | null;
  people: string[];
  request: { id: number; declined_at: string | null } | null;
}

/**
 * Finding one's family, or starting it. With no family and no guests allowed, it is all there is
 * for someone until then, and the way back out; in the wrong family, the way to the right one
 * in one step: they stay in theirs until the other accepts them.
 */
export default function OwnFamily({
  types,
  families,
  own,
  can_leave_family: canLeaveFamily,
  locked,
}: {
  types: Ref[] | null;
  families: FamilyOption[];
  own: { name: string; alone: boolean } | null;
  can_leave_family: boolean;
  locked: boolean;
}) {
  const project = usePage().props.project!;
  const [query, setQuery] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const waiting = families.find((family) => family.request && !family.request.declined_at);
  const listed = families.filter((family) => matches(query, family.name, ...family.people));

  const ask = async (family: FamilyOption) => {
    const ok =
      !own?.alone ||
      (await confirm({
        title: _('You are the only one in {family}', { family: own.name }),
        message: _(
          'If the {other} family accepts you, {family} is left with nobody and is removed. Ask anyway?',
          { other: family.name, family: own.name },
        ),
        confirmLabel: _('Ask to join'),
      }));
    if (ok) router.post(route('family.requests.store', family.id), {}, { preserveScroll: true });
  };
  const cancel = (id: number) =>
    router.delete(route('family.requests.destroy', id), { preserveScroll: true });
  const leaveFamily = async () => {
    const ok = await confirm({
      title: _('Leave the {family} family?', { family: own!.name }),
      message: own!.alone
        ? _('You are the only one in it: it is removed. You stay in the project.')
        : _('You stay in the project.'),
      confirmLabel: _('Leave'),
      danger: true,
    });
    if (ok) router.delete(route('family.leave'));
  };
  const leaveProject = async () => {
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
        title={own ? _('In the wrong family?') : _('Find your family')}
        description={
          own
            ? _(
                'You are in the {family} family. Ask to join yours, or create it: you stay where you are until it is settled.',
                { family: own.name },
              )
            : _(
                'Everyone in {project} takes part as a family. If yours is already here, ask to join it; if not, create it.',
                { project: project.name },
              )
        }
      />
      {locked ? (
        <Alert color="yellow" radius="lg" mb="md">
          {_('The lottery has run: families no longer change.')}
        </Alert>
      ) : null}
      {waiting ? (
        <Alert color="blue" radius="lg" mb="md" className="family-waiting">
          <Group justify="space-between" gap="sm">
            <Text size="sm">
              {_('Waiting for the {family} family to answer.', { family: waiting.name })}
            </Text>
            <Button size="xs" variant="default" onClick={() => cancel(waiting.request!.id)}>
              {_('Cancel')}
            </Button>
          </Group>
        </Alert>
      ) : null}
      <Group justify="space-between" mb="md" gap="sm">
        <QuerySearch
          value={query}
          placeholder={_('Search families or people')}
          maw={360}
          onSearch={(words) => setQuery(words || null)}
        />
        {types?.length ? (
          <Button variant="default" onClick={() => setCreating(true)}>
            {_('Mine is not here: create it')}
          </Button>
        ) : null}
      </Group>
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
        {listed.map((family) => (
          <Card key={family.id} p="md" className="family-option">
            <Group wrap="nowrap" align="flex-start">
              <FamilyMark name={family.name} size={44} />
              <Stack gap={4} style={{ minWidth: 0, flex: 1 }}>
                <Text fw={700}>{family.name}</Text>
                {family.unit_type ? <Text className="ds-caption">{family.unit_type}</Text> : null}
                <Text size="sm">{family.people.join(', ')}</Text>
                <div>
                  {family.request?.declined_at ? (
                    <Badge color="gray">{_('Declined your request')}</Badge>
                  ) : family.request ? (
                    <Badge color="blue">{_('Waiting for an answer')}</Badge>
                  ) : waiting || locked ? null : (
                    <Button size="xs" variant="light" mt={4} onClick={() => void ask(family)}>
                      {_('Ask to join')}
                    </Button>
                  )}
                </div>
              </Stack>
            </Group>
          </Card>
        ))}
      </SimpleGrid>
      {!listed.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {query ? _('No family or person matches “{q}”.', { q: query }) : _('No families yet.')}
        </Text>
      ) : null}
      <Group mt="xl" gap="sm">
        {canLeaveFamily ? (
          <Button variant="subtle" color="gray" onClick={() => void leaveFamily()}>
            {_('Leave my family')}
          </Button>
        ) : null}
        {!own ? (
          <Button variant="subtle" color="gray" onClick={() => void leaveProject()}>
            {_('Leave the project')}
          </Button>
        ) : null}
      </Group>
      <Modal opened={creating} onClose={() => setCreating(false)} title={_('Create your family')}>
        {types?.length ? (
          <FamilyForm own unitTypes={types} onDone={() => setCreating(false)} />
        ) : null}
      </Modal>
    </>
  );
}
