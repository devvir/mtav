import { TextInput, type TextInputProps } from '@mantine/core';
import { IconSearch } from '@tabler/icons-react';

/** A search box: a magnifier, the words typed (the caller decides when to search) */
export function SearchField(props: TextInputProps) {
  return <TextInput type="search" leftSection={<IconSearch size={18} />} radius="xl" {...props} />;
}
