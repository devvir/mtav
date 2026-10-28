import { SearchField } from '@/design';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { useDebouncedCallback } from '@mantine/hooks';
import { useState } from 'react';

/** Filters the current page by a query (?q=…) as one types */
export function QuerySearch({
  value,
  placeholder,
  param = 'q',
  maw = 360,
}: {
  value?: string | null;
  placeholder?: string;
  param?: string;
  maw?: number;
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
        search(event.currentTarget.value);
      }}
      maw={maw}
      w="100%"
    />
  );
}
