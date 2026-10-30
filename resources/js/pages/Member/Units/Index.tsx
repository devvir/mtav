import { PlanPanel } from '@/components/domain/PlanPanel';
import { UnitDetails } from '@/components/domain/UnitDetails';
import { UnitsByType } from '@/components/domain/UnitsByType';
import { UnitForm } from '@/components/forms/UnitForm';
import { UnitTypeForm } from '@/components/forms/UnitTypeForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { useProjectChanges } from '@/lib/realtime';
import { _ } from '@/lib/translations';
import type { Plan, Unit, UnitType } from '@/types/models';
import { Deferred, usePage } from '@inertiajs/react';
import { Button, Group, Modal, Skeleton, Stack } from '@mantine/core';
import { IconBuildingCommunity, IconPlus, IconStack2 } from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  unitTypes: UnitType[];
  plan?: Plan;
  family: { id: number; name: string; unit_type_id: number } | null;
  /** A unit's details, shown over the page (?unit=7) */
  unit: Unit | null;
  /** Reached at units/edit: the plan opens for drawing */
  editPlan: boolean;
}

/**
 * The project's units: where they are on the plan, and by type. Admins add types and units
 * here (dialogs; ?new_type=1 and ?new_unit=1 open them), change or remove a type, and draw
 * the plan in place (at units/edit, open for drawing from the start).
 */
export default function UnitsIndex({ unitTypes, plan, family, unit: shown, editPlan }: Props) {
  const details = useDetailsInAddress('unit');
  const canManage = usePage().props.project!.can.manage;
  const noUnits = unitTypes.every((type) => !type.units?.length);
  const addingType = useAddressedDialog('new_type', canManage);
  const addingUnits = useAddressedDialog('new_unit', canManage);
  const [editingType, setEditingType] = useState<UnitType | null>(null);
  useProjectChanges({
    units: ['unitTypes', 'plan', 'unit'],
    unit_types: ['unitTypes'],
    families: ['unitTypes'],
    lottery_runs: ['unitTypes', 'plan'],
    plan: ['plan'],
  });
  const ownUnit =
    plan?.items.find((item) => item.unit?.family?.id === family?.id)?.unit?.id ?? null;

  return (
    <>
      <PageHeader
        title={_('Units')}
        description={_("The project's dwellings, by type.")}
        help={
          <InfoTip
            title={_('What is a unit type?')}
            tour={canManage ? 'manage.unit_types' : undefined}
          >
            {_(
              "A group of equivalent units, like '2 bedrooms'. Each family has one type, and receives a unit of that type: each type has, in fact, its own lottery.",
            )}
          </InfoTip>
        }
        actions={
          canManage ? (
            <Group gap="sm">
              <Button
                data-tour="unit_types.add"
                variant="default"
                leftSection={<IconStack2 size={18} />}
                onClick={addingType.open}
              >
                {_('Add a unit type')}
              </Button>
              <Button leftSection={<IconPlus size={18} />} onClick={addingUnits.open}>
                {_('Add units')}
              </Button>
            </Group>
          ) : null
        }
      />
      <Stack gap="lg">
        {noUnits ? (
          <EmptyState
            icon={IconBuildingCommunity}
            title={_('No units yet')}
            action={
              !canManage ? null : unitTypes.length ? (
                <Button onClick={addingUnits.open}>{_('Add the first unit')}</Button>
              ) : (
                <Button onClick={addingType.open}>{_('Add the first unit type')}</Button>
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
        ) : null}

        {unitTypes.length ? (
          <UnitsByType unitTypes={unitTypes} onEditType={canManage ? setEditingType : undefined} />
        ) : null}

        {/* Admins may draw the plan before any unit exists */}
        {!noUnits || canManage ? (
          <Deferred data="plan" fallback={<Skeleton h={260} radius="lg" />}>
            {plan ? (
              <PlanPanel
                plan={plan}
                canEdit={canManage}
                editAtOnce={editPlan}
                unitTypes={canManage ? unitTypes : undefined}
                highlightType={family?.unit_type_id}
                ownUnit={ownUnit}
                aside={family ? _("Units of your family's type are highlighted.") : undefined}
              />
            ) : null}
          </Deferred>
        ) : null}
      </Stack>

      {canManage ? (
        <>
          <Modal opened={addingType.opened} onClose={addingType.close} title={_('Add a unit type')}>
            <UnitTypeForm onDone={addingType.close} />
          </Modal>
          <Modal
            opened={Boolean(editingType)}
            onClose={() => setEditingType(null)}
            title={editingType?.name}
          >
            {editingType ? (
              <UnitTypeForm unitType={editingType} onDone={() => setEditingType(null)} />
            ) : null}
          </Modal>
          <Modal
            opened={addingUnits.opened}
            onClose={addingUnits.close}
            size="lg"
            title={
              <Group gap={6}>
                {_('Add units')}
                <InfoTip title={_('Adding units')} tour="manage.units">
                  {_(
                    "Add the project's units one by one, each with its type. Every new unit also appears on the plan, ready to be placed.",
                  )}
                </InfoTip>
              </Group>
            }
          >
            <UnitForm unitTypes={unitTypes} onDone={addingUnits.close} />
          </Modal>
        </>
      ) : null}

      <Modal opened={Boolean(shown)} onClose={details.close} title={shown?.identifier}>
        {shown ? <UnitDetails unit={shown} unitTypes={canManage ? unitTypes : null} /> : null}
      </Modal>
    </>
  );
}
