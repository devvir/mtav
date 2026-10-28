import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Paginated, Ref, Unit } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import {
  ActionIcon,
  Button,
  Card,
  Group,
  NativeSelect,
  Table,
  VisuallyHidden,
} from '@mantine/core';
import { IconLayoutGrid, IconPencil, IconPlus, IconTrash } from '@tabler/icons-react';

interface Props {
  units: Paginated<Unit>;
  unitTypes: Ref[];
  filters: { q: string | null; unit_type: number | null };
}

export default function UnitsIndex({ units, unitTypes, filters }: Props) {
  useProjectChanges({ units: ['units'], lottery_runs: ['units'] });

  const filterByType = (unitType: string) =>
    router.get(
      route('manage.units.index'),
      { ...(unitType ? { unit_type: unitType } : {}), ...(filters.q ? { q: filters.q } : {}) },
      { preserveState: true },
    );

  const remove = async (unit: Unit) => {
    if (
      await confirm({
        title: _('Remove unit {name}?', { name: unit.identifier }),
        confirmLabel: _('Remove'),
        danger: true,
      })
    ) {
      router.delete(route('manage.units.destroy', unit.id), { preserveScroll: true });
    }
  };

  return (
    <>
      <PageHeader
        title={_('Units')}
        actions={
          <Button
            component={Link}
            href={route(
              'manage.units.create',
              filters.unit_type ? { unit_type: filters.unit_type } : {},
            )}
            leftSection={<IconPlus size={18} />}
          >
            {_('Add units')}
          </Button>
        }
      />
      <Group mb="md" gap="sm">
        <QuerySearch value={filters.q} />
        <NativeSelect
          aria-label={_('Unit type')}
          value={filters.unit_type ? String(filters.unit_type) : ''}
          onChange={(event) => filterByType(event.currentTarget.value)}
          data={[
            { value: '', label: _('All types') },
            ...unitTypes.map((type) => ({ value: String(type.id), label: type.name })),
          ]}
          w={220}
        />
      </Group>
      {units.data.length ? (
        <Card p={0}>
          <Table.ScrollContainer minWidth={520}>
            <Table highlightOnHover verticalSpacing="sm" horizontalSpacing="md">
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>{_('Identifier')}</Table.Th>
                  <Table.Th>{_('Type')}</Table.Th>
                  <Table.Th>{_('Assigned to')}</Table.Th>
                  <Table.Th>
                    <VisuallyHidden>{_('Actions')}</VisuallyHidden>
                  </Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                {units.data.map((unit) => (
                  <Table.Tr key={unit.id}>
                    <Table.Td fw={700}>{unit.identifier}</Table.Td>
                    <Table.Td>{unit.type?.name}</Table.Td>
                    <Table.Td c={unit.family ? 'var(--mtav-positive)' : 'dimmed'}>
                      {unit.family?.name ?? '—'}
                    </Table.Td>
                    <Table.Td>
                      <Group gap={4} justify="flex-end" wrap="nowrap">
                        <ActionIcon
                          size="lg"
                          component={Link}
                          href={route('manage.units.edit', unit.id)}
                          aria-label={_('Edit {name}', { name: unit.identifier })}
                        >
                          <IconPencil size={18} />
                        </ActionIcon>
                        <ActionIcon
                          size="lg"
                          color="red"
                          disabled={Boolean(unit.family)}
                          aria-label={_('Remove {name}', { name: unit.identifier })}
                          onClick={() => remove(unit)}
                        >
                          <IconTrash size={18} />
                        </ActionIcon>
                      </Group>
                    </Table.Td>
                  </Table.Tr>
                ))}
              </Table.Tbody>
            </Table>
          </Table.ScrollContainer>
        </Card>
      ) : (
        <EmptyState icon={IconLayoutGrid} title={_('No units')}>
          {unitTypes.length ? null : _('Add unit types first, then their units.')}
        </EmptyState>
      )}
      <Pagination page={units} />
    </>
  );
}
