import { changeLanguage } from '@/lib/language';
import { _, getLocale } from '@/lib/translations';
import { Button, Menu } from '@mantine/core';
import { IconCheck, IconLanguage } from '@tabler/icons-react';

/** The language of the page, each named in itself; the account they sign in to keeps it */
export function LanguagePicker({ locales }: { locales: Record<string, string> }) {
  const current = getLocale();
  const pick = (locale: string) => locale !== current && changeLanguage(locale);

  return (
    <Menu position="bottom-end" withinPortal>
      <Menu.Target>
        <Button variant="subtle" color="gray" leftSection={<IconLanguage size={18} />}>
          {locales[current] ?? current}
        </Button>
      </Menu.Target>
      <Menu.Dropdown aria-label={_('Language')}>
        {Object.entries(locales).map(([locale, name]) => (
          <Menu.Item
            key={locale}
            lang={locale.replace('_', '-')}
            onClick={() => pick(locale)}
            // Room kept for the mark, so names stay in line
            rightSection={
              <IconCheck
                size={16}
                style={{ visibility: locale === current ? 'visible' : 'hidden' }}
              />
            }
          >
            {name}
          </Menu.Item>
        ))}
      </Menu.Dropdown>
    </Menu>
  );
}
