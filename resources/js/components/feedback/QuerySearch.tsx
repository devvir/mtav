import { SearchField } from '@/design';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
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

  const search = useDebouncedCallback((query: string) => {
    const url = new URL(window.location.href);
    if (query) url.searchParams.set(param, query);
    else url.searchParams.delete(param);
    url.searchParams.delete('page');
    router.get(
      url.pathname + url.search,
      {},
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }, 350);

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
