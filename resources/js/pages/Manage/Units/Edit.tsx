import { UnitForm } from '@/components/forms/UnitForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Unit, UnitType } from '@/types/models';

export default function UnitEdit({ unit, unitTypes }: { unit: Unit; unitTypes: UnitType[] }) {
  return (
    <>
      <PageHeader
        title={unit.identifier}
        back={{ href: route('manage.units.index'), label: _('Units') }}
      />
      <UnitForm unit={unit} unitTypes={unitTypes} />
    </>
  );
}
