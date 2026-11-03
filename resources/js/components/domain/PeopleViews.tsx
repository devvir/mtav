import { FamilyCard, PersonCard } from '@/components/domain/PeopleCards';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, Person } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Group, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import type { ReactNode } from 'react';

/** What one search found, whichever page it started from: each kind apart */
export type Found = { families: Family[]; members: Person[]; admins: Person[] };

/**
 * The cooperative's people, three ways: by family, every member, or the admins. One place in
 * the navigation; each view keeps its own address and details, and one search looks in all
 * three. A view's own link starts it afresh, its search left behind.
 */
export function PeopleViews({ q }: { q: string | null }) {
  const page = usePage<{ filters?: { admins?: boolean } }>();
  const admins = routeIs('people.*') && Boolean(page.props.filters?.admins);
  const views = [
    { label: _('Families'), href: route('families.index'), active: routeIs('families.*') },
    { label: _('Members'), href: route('people.index'), active: routeIs('people.*') && !admins },
    { label: _('Admins'), href: route('people.index', { admins: 1 }), active: admins },
  ];

  return (
    <Group mb="md" gap="sm" justify="space-between" wrap="wrap">
      <nav className="people-views" aria-label={_('How to see them')} data-tour="people.views">
        {views.map((view) => (
          <Link
            key={view.label}
            href={view.href}
            className="ds-pill floor-tab"
            data-active={view.active || undefined}
            aria-current={view.active ? 'page' : undefined}
          >
            {view.label}
          </Link>
        ))}
      </nav>
      <QuerySearch
        key={q ? 'searching' : 'afresh'}
        value={q}
        placeholder={_('Search families or people')}
        maw={360}
      />
    </Group>
  );
}

/** What a search found: families, members and admins, each under its own heading */
export function FoundPeople({ found, q, own }: { found: Found; q: string; own?: number }) {
  const groups: { title: string; count: number; items: ReactNode }[] = [
    {
      title: _('Families'),
      count: found.families.length,
      items: found.families.map((family) => (
        <FamilyCard
          key={family.id}
          family={family}
          own={family.id === own}
          href={route('families.index', { family: family.id })}
        />
      )),
    },
    {
      title: _('Members'),
      count: found.members.length,
      items: found.members.map((person) => (
        <PersonCard
          key={person.id}
          person={person}
          href={route('people.index', { person: person.id })}
        />
      )),
    },
    {
      title: _('Admins'),
      count: found.admins.length,
      items: found.admins.map((person) => (
        <PersonCard
          key={person.id}
          person={person}
          href={route('people.index', { admins: 1, person: person.id })}
        />
      )),
    },
  ].filter((group) => group.count);

  if (!groups.length) {
    return (
      <Text ta="center" c="dimmed" py="xl">
        {_('No family or person matches “{q}”.', { q })}
      </Text>
    );
  }

  return (
    <Stack gap="lg">
      {groups.map((group) => (
        <section key={group.title} aria-label={group.title}>
          <Title order={2} size="h4" mb="sm">
            {group.title}{' '}
            <Text span c="dimmed" fw={500} fz="sm">
              {group.count}
            </Text>
          </Title>
          <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>{group.items}</SimpleGrid>
        </section>
      ))}
    </Stack>
  );
}
