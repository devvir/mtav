import { SearchField } from '@/design';
import { setFilter } from '@/lib/listFilters';
import { _ } from '@/lib/translations';
import { useDebouncedCallback } from '@mantine/hooks';
import { useState } from 'react';

/**
 * Filters the current page by a query (?q=…) as one types: asking the server, or, given
 * `onSearch`, narrowing a list the page holds whole, at once.
 */
export function QuerySearch({
  value,
  placeholder,
  param = 'q',
  maw = 360,
  onSearch,
}: {
  value?: string | null;
  placeholder?: string;
  param?: string;
  maw?: number;
  onSearch?: (query: string) => void;
}) {
  const [text, setText] = useState(value ?? '');

  const search = useDebouncedCallback((query: string) => setFilter(param, query), 350);

  return (
    <SearchField
      aria-label={placeholder ?? _('Search')}
      placeholder={placeholder ?? _('Search')}
      value={text}
      onChange={(event) => {
        setText(event.currentTarget.value);
        if (onSearch) onSearch(event.currentTarget.value);
        else search(event.currentTarget.value);
      }}
      maw={maw}
      w="100%"
    />
  );
}
