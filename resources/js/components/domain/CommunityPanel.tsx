import { FoldablePanel } from '@/components/layout/FoldablePanel';
import { FamilyMark, PersonAvatar } from '@/design';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Family, Person } from '@/types/models';
import { Link } from '@inertiajs/react';
import { Card, Group, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import { IconUsersGroup } from '@tabler/icons-react';

export interface Community {
  families: Family[];
  admins: Person[];
  latest: Person[];
}

/** One person: their details open over the home page */
function PersonRow({ person, caption }: { person: Person; caption?: string | null }) {
  return (
    <Card
      component={Link}
      href={route('projects.show', { person: person.id })}
      only={['person']}
      preserveState
      preserveScroll
      p="xs"
      className="ds-interactive"
    >
      <Group gap="sm" wrap="nowrap">
        <PersonAvatar name={person.name} src={person.avatar} size={32} />
        <div style={{ minWidth: 0 }}>
          <Text fw={650} size="sm" className="ds-truncate" title={person.name}>
            {person.name}
          </Text>
          {caption ? (
            <Text className="ds-caption ds-truncate" title={caption}>
              {caption}
            </Text>
          ) : null}
        </div>
      </Group>
    </Card>
  );
}

/**
 * Who the project is: some of its families, who joined last, and its admins. Each opens its
 * details over the home page; the title leads to the people's page.
 */
export function CommunityPanel({ community }: { community: Community }) {
  return (
    <FoldablePanel
      name="community"
      title={_('The people of the project')}
      icon={IconUsersGroup}
      tone="brand"
      href={route('families.index')}
    >
      <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
        <div>
          <Title order={3} fz="sm" mb="xs" className="ds-label">
            {_('Families')}
          </Title>
          <Stack gap="xs">
            {community.families.map((family) => (
              <Card
                key={family.id}
                component={Link}
                href={route('projects.show', { family: family.id })}
                only={['family', 'requests']}
                preserveState
                preserveScroll
                p="xs"
                className="ds-interactive"
              >
                <Group gap="sm" wrap="nowrap">
                  <FamilyMark name={family.name} size={32} />
                  <div style={{ minWidth: 0 }}>
                    <Text fw={650} size="sm" className="ds-truncate" title={family.name}>
                      {family.name}
                    </Text>
                    <Text className="ds-caption">
                      {_n('{count} person', '{count} people', family.members_count ?? 0)}
                    </Text>
                  </div>
                </Group>
              </Card>
            ))}
          </Stack>
        </div>
        <div>
          <Title order={3} fz="sm" mb="xs" className="ds-label">
            {_('Joined lately')}
          </Title>
          <Stack gap="xs">
            {community.latest.map((person) => (
              <PersonRow key={person.id} person={person} caption={person.family?.name} />
            ))}
          </Stack>
        </div>
        <div>
          <Title order={3} fz="sm" mb="xs" className="ds-label">
            {_('Admins')}
          </Title>
          <Stack gap="xs">
            {community.admins.map((person) => (
              <PersonRow key={person.id} person={person} caption={person.email} />
            ))}
          </Stack>
        </div>
      </SimpleGrid>
    </FoldablePanel>
  );
}
