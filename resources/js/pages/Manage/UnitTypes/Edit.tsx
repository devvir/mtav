import { UnitTypeForm } from '@/components/forms/UnitTypeForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';

export default function UnitTypeEdit({ unitType }: { unitType: UnitType }) {
  return (
    <>
      <PageHeader
        title={unitType.name}
        back={{ href: route('manage.unit_types.index'), label: _('Unit types') }}
      />
      <UnitTypeForm unitType={unitType} />
    </>
  );
}
