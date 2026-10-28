import { FloorPlan } from '@/components/domain/FloorPlan';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Panel } from '@/design';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { Plan, UnitType } from '@/types/models';
import { Deferred, Link, usePage } from '@inertiajs/react';
import { Button, Card, SimpleGrid, Skeleton, Stack, Text } from '@mantine/core';
import { IconBuildingCommunity, IconLayoutGrid, IconMap } from '@tabler/icons-react';

interface Props {
  unitTypes: UnitType[];
  plan?: Plan;
  family: { id: number; name: string; unit_type_id: number } | null;
}

/** The project's units: where they are on the plan, and by type */
export default function UnitsIndex({ unitTypes, plan, family }: Props) {
  const canManage = usePage().props.project!.can.manage;
  const noUnits = unitTypes.every((type) => !type.units?.length);
  useProjectChanges({
    units: ['unitTypes', 'plan'],
    unit_types: ['unitTypes'],
    lottery_runs: ['unitTypes', 'plan'],
    plan: ['plan'],
  });
  const ownUnit =
    plan?.items.find((item) => item.unit?.family?.id === family?.id)?.unit?.id ?? null;

  return (
    <>
      <PageHeader
        title={_('Units')}
        description={_(
          "The project's dwellings, by type. Families compete only for units of their own type.",
        )}
      />
      {noUnits ? (
        <EmptyState
          icon={IconBuildingCommunity}
          title={_('No units yet')}
          action={
            !canManage ? null : unitTypes.length ? (
              <Button component={Link} href={route('manage.units.create')}>
                {_('Add the first unit')}
              </Button>
            ) : (
              <Button component={Link} href={route('manage.unit_types.create')}>
                {_('Add the first unit type')}
              </Button>
            )
          }
        >
          {!canManage
            ? _("The admins add the project's units here, by type, and place them on the plan.")
            : unitTypes.length
              ? _('Add the units of each type; they appear on the plan, ready to be placed.')
              : _(
                  'Start with the types of unit (2 bedrooms, 3 bedrooms…), then add the units of each.',
                )}
        </EmptyState>
      ) : (
        <Stack gap="lg">
          <Panel
            title={_('Plan')}
            icon={IconMap}
            tone="accent"
            aside={family ? _("Units of your family's type are highlighted.") : undefined}
          >
            <Deferred data="plan" fallback={<Skeleton h={260} radius="lg" />}>
              {plan ? (
                <FloorPlan plan={plan} highlightType={family?.unit_type_id} ownUnit={ownUnit} />
              ) : null}
            </Deferred>
          </Panel>
          {unitTypes.map((type) => (
            <Panel
              key={type.id}
              title={type.name}
              icon={IconLayoutGrid}
              aside={[
                type.description,
                [
                  _n('{count} unit', '{count} units', type.units?.length ?? 0),
                  _n('{count} family', '{count} families', type.families_count ?? 0),
                ].join(', '),
              ]
                .filter(Boolean)
                .join(' · ')}
            >
              <SimpleGrid cols={{ base: 2, sm: 3, lg: 5 }}>
                {type.units?.map((unit) => (
                  <Card
                    key={unit.id}
                    component={Link}
                    href={route('units.show', unit.id)}
                    p="sm"
                    className="ds-interactive"
                  >
                    <Text fw={700}>{unit.identifier}</Text>
                    <Text
                      size="sm"
                      className="ds-truncate"
                      c={unit.family ? 'var(--mtav-positive)' : 'dimmed'}
                    >
                      {unit.family?.name ?? _('Unassigned')}
                    </Text>
                  </Card>
                ))}
              </SimpleGrid>
            </Panel>
          ))}
        </Stack>
      )}
    </>
  );
}
