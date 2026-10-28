import { PageHeader } from '@/components/layout/PageHeader';
import { FamilyMark, PersonAvatar } from '@/design';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Alert, Button, Card, Group, SimpleGrid, Text } from '@mantine/core';
import { IconHome, IconUserPlus } from '@tabler/icons-react';

export default function FamilyShow({ family }: { family: Family }) {
  const isOwn = usePage().props.project?.family?.id === family.id;

  return (
    <>
      <PageHeader
        title={family.name}
        description={family.unit_type?.name}
        back={{ href: route('families.index'), label: _('Families') }}
        actions={
          isOwn ? (
            <Button
              component={Link}
              href={route('invitations.index')}
              leftSection={<IconUserPlus size={18} />}
            >
              {_('Invite someone')}
            </Button>
          ) : null
        }
      />
      <Group mb="lg">
        <FamilyMark name={family.name} size={64} />
      </Group>
      {family.unit ? (
        <Alert color="green" radius="lg" icon={<IconHome size={20} />} mb="lg">
          {_('Received unit {unit} in the lottery.', { unit: family.unit.identifier })}
        </Alert>
      ) : null}
      <SimpleGrid cols={{ base: 1, sm: 2 }}>
        {family.members?.map((person) => (
          <Card
            key={person.id}
            component={Link}
            href={route('people.show', person.id)}
            p="sm"
            className="ds-interactive"
          >
            <Group>
              <PersonAvatar name={person.name} src={person.avatar} size={44} />
              <Text fw={650}>{person.name}</Text>
            </Group>
          </Card>
        ))}
      </SimpleGrid>
      {!family.members?.length ? (
        <Text ta="center" c="dimmed" py="xl">
          {_('Nobody has joined this family yet.')}
        </Text>
      ) : null}
    </>
  );
}
