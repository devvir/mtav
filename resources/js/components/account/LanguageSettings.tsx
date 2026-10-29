import { route } from '@/lib/route';
import { _, getLocale } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { SimpleGrid, Text, UnstyledButton } from '@mantine/core';
import { IconCheck } from '@tabler/icons-react';
import { useState } from 'react';

/**
 * The language the app (and its mail) speaks to them, on every device: each named in
 * itself, so anyone finds theirs. Picking one reloads the app in it.
 */
export function LanguageSettings({ languages }: { languages: Record<string, string> }) {
  const current = getLocale();
  const [saving, setSaving] = useState<string | null>(null);
  const pick = (locale: string) =>
    locale !== current &&
    router.put(
      route('language'),
      { locale },
      {
        onStart: () => setSaving(locale),
        // The words of the new language come with a fresh start of the app
        onSuccess: () => window.location.reload(),
        onError: () => setSaving(null),
      },
    );

  return (
    <div>
      <Text fw={650} mb={4}>
        {_('Language')}
      </Text>
      <Text className="ds-caption" mb="xs">
        {_('On every device, and in the emails you receive.')}
      </Text>
      <SimpleGrid cols={{ base: 2, sm: 3 }}>
        {Object.entries(languages).map(([locale, name]) => (
          <UnstyledButton
            key={locale}
            lang={locale.replace('_', '-')}
            className="choice-tile ds-surface"
            data-active={locale === current || undefined}
            aria-pressed={locale === current}
            disabled={Boolean(saving)}
            onClick={() => pick(locale)}
          >
            <span style={{ flex: 1 }}>{name}</span>
            {/* Room kept for the mark, so names stay in line */}
            <IconCheck
              size={18}
              style={{ visibility: locale === current ? 'visible' : 'hidden' }}
            />
          </UnstyledButton>
        ))}
      </SimpleGrid>
    </div>
  );
}
