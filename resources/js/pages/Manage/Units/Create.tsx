import { UnitForm } from '@/components/forms/UnitForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';

export default function UnitCreate({
  unitTypes,
  unitType,
}: {
  unitTypes: UnitType[];
  unitType: number | null;
}) {
  return (
    <>
      <PageHeader
        title={_('Add units')}
        back={{ href: route('manage.units.index'), label: _('Units') }}
        help={
          <InfoTip title={_('Adding units')} tour="manage.units">
            {_(
              "Add the project's units one by one, each with its type. Every new unit also appears on the plan, ready to be placed.",
            )}
          </InfoTip>
        }
      />
      <UnitForm unitTypes={unitTypes} unitType={unitType} />
    </>
  );
}
