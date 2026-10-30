import { useAppearance, type TextSize } from '@/design';
import { route } from '@/lib/route';
import { _, getLocale } from '@/lib/translations';
import { router, usePage } from '@inertiajs/react';
import {
  ActionIcon,
  Menu,
  Tooltip,
  useMantineColorScheme,
  type MantineColorScheme,
} from '@mantine/core';
import {
  IconCheck,
  IconDeviceDesktop,
  IconLanguage,
  IconMoon,
  IconSun,
  IconTextSize,
  type Icon,
} from '@tabler/icons-react';
import type { ReactNode } from 'react';

/**
 * One preference: an icon that opens a small list of its options, the chosen one marked (the
 * mark's room kept in every row, so the names stay in line).
 */
function Preference({
  label,
  icon: Glyph,
  options,
  value,
  onChange,
}: {
  label: string;
  icon: Icon;
  options: { value: string; label: ReactNode; lang?: string }[];
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <Menu position="bottom-end" width={190}>
      <Menu.Target>
        <Tooltip label={label} openDelay={400}>
          <ActionIcon variant="subtle" color="gray" size="lg" aria-label={label}>
            <Glyph size={20} />
          </ActionIcon>
        </Tooltip>
      </Menu.Target>
      <Menu.Dropdown>
        {options.map((option) => (
          <Menu.Item
            key={option.value}
            lang={option.lang}
            aria-current={option.value === value ? 'true' : undefined}
            onClick={() => option.value !== value && onChange(option.value)}
            rightSection={
              <IconCheck
                size={16}
                style={{ visibility: option.value === value ? undefined : 'hidden' }}
              />
            }
          >
            {option.label}
          </Menu.Item>
        ))}
      </Menu.Dropdown>
    </Menu>
  );
}

/**
 * Text size, language, and light or dark: where people look for them, apart from the bell and
 * the account. On phones there is no room: they stay in Preferences (where they also are on
 * larger screens, for whoever looks there).
 */
export function HeaderPreferences() {
  const { languages } = usePage().props;
  const { colorScheme, setColorScheme } = useMantineColorScheme();
  const { textSize, setTextSize } = useAppearance();

  const pickLanguage = (locale: string) =>
    router.put(
      route('language'),
      { locale },
      // The words of the new language come with a fresh start of the app
      { onSuccess: () => window.location.reload() },
    );

  return (
    <div className="shell-preferences">
      <Preference
        label={_('Text size')}
        icon={IconTextSize}
        value={textSize}
        onChange={(value) => setTextSize(value as TextSize)}
        options={[
          { value: 'small', label: _('Small') },
          { value: 'medium', label: _('Medium') },
          { value: 'large', label: _('Large') },
        ]}
      />
      <Preference
        label={_('Language')}
        icon={IconLanguage}
        value={getLocale()}
        onChange={pickLanguage}
        options={Object.entries(languages).map(([locale, name]) => ({
          value: locale,
          label: name,
          lang: locale.replace('_', '-'),
        }))}
      />
      <Preference
        label={_('Light or dark')}
        icon={
          colorScheme === 'dark' ? IconMoon : colorScheme === 'auto' ? IconDeviceDesktop : IconSun
        }
        value={colorScheme}
        onChange={(value) => setColorScheme(value as MantineColorScheme)}
        options={[
          { value: 'light', label: _('Light') },
          { value: 'dark', label: _('Dark') },
          { value: 'auto', label: _('As my device') },
        ]}
      />
    </div>
  );
}
