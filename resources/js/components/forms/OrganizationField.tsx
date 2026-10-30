import { _ } from '@/lib/translations';
import { Autocomplete } from '@mantine/core';

/**
 * The federations housing cooperatives belong to in Uruguay, offered as one types; any
 * other name, or none, is as good
 */
const FEDERATIONS = ['FUCVAM', 'FECOVI'];

/** A project's organization: free text, with the federations at hand */
export function OrganizationField({
  value,
  onChange,
  error,
  description,
}: {
  value: string;
  onChange: (value: string) => void;
  error?: string;
  description?: string;
}) {
  return (
    <Autocomplete
      name="organization"
      label={_('Organization')}
      description={description}
      data={FEDERATIONS}
      value={value}
      onChange={onChange}
      error={error}
      maxLength={120}
    />
  );
}
