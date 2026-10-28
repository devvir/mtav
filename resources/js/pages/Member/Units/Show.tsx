import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Unit } from '@/types/models';
import { Link } from '@inertiajs/react';
import { Anchor, Card, Text } from '@mantine/core';

export default function UnitShow({ unit }: { unit: Unit }) {
  return (
    <>
      <PageHeader
        title={unit.identifier}
        description={unit.type?.name}
        back={{ href: route('units.index'), label: _('Units') }}
      />
      <Card p="lg" maw={560}>
        {unit.family ? (
          <Text>
            {_('Assigned to')}{' '}
            <Anchor component={Link} href={route('families.show', unit.family.id)} fw={650}>
              {unit.family.name}
            </Anchor>
          </Text>
        ) : (
          <Text c="dimmed">
            {_('Not assigned yet: the lottery decides which family receives it.')}
          </Text>
        )}
      </Card>
    </>
  );
}
