import { FamilyForm } from '@/components/forms/FamilyForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Family, UnitType } from '@/types/models';

export default function FamilyEdit({
  family,
  unitTypes,
}: {
  family: Family;
  unitTypes: UnitType[];
}) {
  return (
    <>
      <PageHeader
        title={family.name}
        back={{ href: route('manage.families.index'), label: _('Families') }}
      />
      <FamilyForm family={family} unitTypes={unitTypes} />
    </>
  );
}
