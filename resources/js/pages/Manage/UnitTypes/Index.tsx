import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState, Glyph, Status } from '@/design';
import { confirm } from '@/lib/dialogs';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import type { UnitType } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { ActionIcon, Button, Card, Group, Text } from '@mantine/core';
import { IconPencil, IconPlus, IconStack2, IconTrash } from '@tabler/icons-react';

export default function UnitTypesIndex({ unitTypes }: { unitTypes: UnitType[] }) {
  useProjectChanges({ unit_types: ['unitTypes'], units: ['unitTypes'], families: ['unitTypes'] });

  const remove = async (type: UnitType) => {
    if (
      await confirm({
        title: _('Remove {name}?', { name: type.name }),
        confirmLabel: _('Remove'),
        danger: true,
      })
    ) {
      router.delete(route('manage.unit_types.destroy', type.id), { preserveScroll: true });
    }
  };

  return (
    <>
      <PageHeader
        title={_('Unit types')}
        description={_(
          'Groups of equivalent units. Each family competes only for units of its type.',
        )}
        help={
          <InfoTip title={_('What is a unit type?')} tour="manage.unit_types">
            {_(
              "A group of equivalent units, like '2 bedrooms'. Each family has one type, and competes only for units of that type: each type is, in fact, its own lottery.",
            )}
          </InfoTip>
        }
        actions={
          <Button
            data-tour="unit_types.add"
            component={Link}
            href={route('manage.unit_types.create')}
            leftSection={<IconPlus size={18} />}
          >
            {_('Add a unit type')}
          </Button>
        }
      />
      {unitTypes.length ? (
        <Card p={0} data-tour="unit_types.list">
          {unitTypes.map((type) => (
            <Group key={type.id} className="row" gap="md" wrap="wrap">
              <Glyph icon={IconStack2} tone="accent" />
              <div style={{ flex: 1, minWidth: 200 }}>
                <Text fw={700}>{type.name}</Text>
                <Group gap="sm">
                  <Text className="ds-caption">
                    {[
                      _n('{count} unit', '{count} units', type.units_count ?? 0),
                      _n('{count} family', '{count} families', type.families_count ?? 0),
                    ].join(', ')}
                  </Text>
                  {type.units_count !== type.families_count ? (
                    <Status tone="warning">{_('not balanced')}</Status>
                  ) : null}
                </Group>
              </div>
              <Group gap={4}>
                <Button
                  variant="default"
                  size="xs"
                  component={Link}
                  href={route('manage.units.create', { unit_type: type.id })}
                  leftSection={<IconPlus size={14} />}
                >
                  {_('Units')}
                </Button>
                <ActionIcon
                  size="lg"
                  component={Link}
                  href={route('manage.unit_types.edit', type.id)}
                  aria-label={_('Edit {name}', { name: type.name })}
                >
                  <IconPencil size={18} />
                </ActionIcon>
                <ActionIcon
                  size="lg"
                  color="red"
                  aria-label={_('Remove {name}', { name: type.name })}
                  disabled={Boolean(type.units_count || type.families_count)}
                  onClick={() => remove(type)}
                >
                  <IconTrash size={18} />
                </ActionIcon>
              </Group>
            </Group>
          ))}
        </Card>
      ) : (
        <EmptyState
          icon={IconStack2}
          title={_('No unit types yet')}
          action={
            <Button component={Link} href={route('manage.unit_types.create')}>
              {_('Add the first one')}
            </Button>
          }
        >
          {_('Start here: for example 2 bedrooms, 3 bedrooms.')}
        </EmptyState>
      )}
    </>
  );
}
