import { FoldablePanel } from '@/components/layout/FoldablePanel';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { UnitType } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { ActionIcon, Group, Text, Tooltip } from '@mantine/core';
import { IconLayoutGrid, IconPencil, IconTrash } from '@tabler/icons-react';

/**
 * The project's units, by type, small: one chip each (with its family, once the lottery
 * gave it one). It folds away, as each person left it, for working on the plan instead.
 * Admins edit or remove a type here.
 */
export function UnitsByType({
  unitTypes,
  onEditType,
  away,
}: {
  unitTypes: UnitType[];
  /** For admins */
  onEditType?: (type: UnitType) => void;
  /** Shown away from the units page: a unit opens there */
  away?: boolean;
}) {
  const total = unitTypes.reduce((sum, type) => sum + (type.units?.length ?? 0), 0);

  const inUse = (type: UnitType) => Boolean(type.units?.length || type.families_count);
  const removeType = async (type: UnitType) => {
    if (
      await confirm({
        title: _('Remove {name}?', { name: type.name }),
        confirmLabel: _('Remove'),
        danger: true,
      })
    )
      router.delete(route('manage.unit_types.destroy', type.id), { preserveScroll: true });
  };

  return (
    <FoldablePanel
      name="units-by-type"
      title={_('Units by type')}
      icon={IconLayoutGrid}
      aside={_n('{count} unit', '{count} units', total)}
      flush
      data-tour="unit_types.list"
    >
      <div className="units-by-type">
        {unitTypes.map((type) => (
          <section key={type.id} className="units-by-type-row">
            <Group justify="space-between" wrap="nowrap" gap="xs">
              <Group gap={8} wrap="nowrap" style={{ minWidth: 0 }}>
                <Text fw={700} size="sm" className="ds-truncate">
                  {type.name}
                </Text>
                <Text className="ds-caption ds-truncate">
                  {[
                    _n('{count} unit', '{count} units', type.units?.length ?? 0),
                    _n('{count} family', '{count} families', type.families_count ?? 0),
                  ].join(', ')}
                </Text>
              </Group>
              {onEditType ? (
                <Group gap={2} wrap="nowrap">
                  <ActionIcon
                    size="sm"
                    variant="subtle"
                    aria-label={_('Edit {name}', { name: type.name })}
                    onClick={() => onEditType(type)}
                  >
                    <IconPencil size={16} />
                  </ActionIcon>
                  <Tooltip
                    label={_('In use by units or families')}
                    disabled={!inUse(type)}
                    withinPortal
                  >
                    <ActionIcon
                      size="sm"
                      variant="subtle"
                      color="red"
                      aria-label={_('Remove {name}', { name: type.name })}
                      disabled={inUse(type)}
                      onClick={() => removeType(type)}
                    >
                      <IconTrash size={16} />
                    </ActionIcon>
                  </Tooltip>
                </Group>
              ) : null}
            </Group>
            {type.units?.length ? (
              <div className="unit-chips">
                {type.units.map((unit) => (
                  <Link
                    key={unit.id}
                    href={route('units.index', { unit: unit.id })}
                    only={away ? undefined : ['unit']}
                    preserveState={!away}
                    preserveScroll={!away}
                    className="unit-chip"
                    data-given={unit.family ? true : undefined}
                  >
                    {unit.identifier}
                    {unit.family ? <span>{unit.family.name}</span> : null}
                  </Link>
                ))}
              </div>
            ) : (
              <Text c="dimmed" size="sm" mt={4}>
                {_('No units of this type yet.')}
              </Text>
            )}
          </section>
        ))}
      </div>
    </FoldablePanel>
  );
}
