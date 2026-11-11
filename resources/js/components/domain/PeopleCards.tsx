import { FamilyMark, PersonAvatar } from '@/design';
import { _, _n } from '@/lib/translations';
import type { Family, Person } from '@/types/models';
import { Link } from '@inertiajs/react';
import { Badge, Card, Group, Text } from '@mantine/core';
import { IconHome } from '@tabler/icons-react';

/** A family on the people's pages: its name, its people, its unit; it opens its details */
export function FamilyCard({ family, href, own }: { family: Family; href: string; own?: boolean }) {
  return (
    <Card
      component={Link}
      href={href}
      only={['family']}
      preserveState
      preserveScroll
      p="md"
      className="ds-interactive"
      data-own={own || undefined}
    >
      <Group wrap="nowrap" align="flex-start">
        <FamilyMark name={family.name} size={44} />
        <div style={{ minWidth: 0, flex: 1 }}>
          <Group gap="xs">
            <Text fw={700}>{family.name}</Text>
            {own ? <Badge color="green">{_('your family')}</Badge> : null}
          </Group>
          <Text className="ds-caption">
            {_n('{count} person', '{count} people', family.members_count ?? 0)}
            {family.unit_type ? ` · ${family.unit_type.name}` : null}
          </Text>
          {family.members?.length ? (
            <Text size="sm" mt={6} lineClamp={2}>
              {family.members.map((member) => member.firstname).join(', ')}
            </Text>
          ) : null}
          {family.unit ? (
            <Group gap={4} mt={6} c="var(--mtav-positive)" fw={650} fz="sm">
              <IconHome size={16} /> {family.unit.identifier}
            </Group>
          ) : null}
        </div>
      </Group>
    </Card>
  );
}

/** A person on the people's pages: name, family, and whether they are an admin or a guest */
export function PersonCard({ person, href }: { person: Person; href: string }) {
  return (
    <Card
      component={Link}
      href={href}
      only={['person']}
      preserveState
      preserveScroll
      p="sm"
      className="ds-interactive"
    >
      <Group wrap="nowrap">
        <PersonAvatar name={person.name} src={person.avatar} size={44} />
        <div style={{ minWidth: 0, flex: 1 }}>
          <Text fw={650} className="ds-truncate" title={person.name}>
            {person.name}
          </Text>
          <Text className="ds-caption ds-truncate" title={person.family?.name}>
            {person.family?.name ?? _('Not joined their family yet')}
          </Text>
        </div>
        {person.is_admin ? <Badge color="blue">{_('Admin')}</Badge> : null}
        {person.is_guest ? <Badge color="gray">{_('Guest')}</Badge> : null}
      </Group>
    </Card>
  );
}
