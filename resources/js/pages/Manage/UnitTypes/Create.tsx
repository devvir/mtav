import { UnitTypeForm } from '@/components/forms/UnitTypeForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';

export default function UnitTypeCreate() {
  return (
    <>
      <PageHeader
        title={_('Add a unit type')}
        back={{ href: route('manage.unit_types.index'), label: _('Unit types') }}
      />
      <UnitTypeForm />
    </>
  );
}
