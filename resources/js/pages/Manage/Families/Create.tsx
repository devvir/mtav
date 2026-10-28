import { FamilyForm } from '@/components/forms/FamilyForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { UnitType } from '@/types/models';

export default function FamilyCreate({ unitTypes }: { unitTypes: UnitType[] }) {
  return (
    <>
      <PageHeader
        title={_('Add a family')}
        back={{ href: route('manage.families.index'), label: _('Families') }}
      />
      <FamilyForm unitTypes={unitTypes} />
    </>
  );
}
