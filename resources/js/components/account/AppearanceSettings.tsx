import { PALETTES, useAppearance, type TextSize } from '@/design';
import { _ } from '@/lib/translations';
import {
  SimpleGrid,
  Stack,
  Text,
  UnstyledButton,
  useMantineColorScheme,
  type MantineColorScheme,
} from '@mantine/core';
import { IconContrast, IconDeviceDesktop, IconMoon, IconSun, type Icon } from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** One choice among a few: a tile, the chosen one lit */
function Option({
  active,
  onClick,
  children,
}: {
  active: boolean;
  onClick: () => void;
  children: ReactNode;
}) {
  return (
    <UnstyledButton
      className="choice-tile ds-surface"
      data-active={active || undefined}
      aria-pressed={active}
      onClick={onClick}
    >
      {children}
    </UnstyledButton>
  );
}

/** The text size, light or dark, the colours, and contrast: kept on this device */
export function AppearanceSettings() {
  const { colorScheme, setColorScheme } = useMantineColorScheme();
  const { palette, setPalette, highContrast, setHighContrast, textSize, setTextSize } =
    useAppearance();

  // Each shown in its own size: an A, small to large
  const sizes: { value: TextSize; label: string; glyph: string }[] = [
    { value: 'small', label: _('Small'), glyph: '0.95rem' },
    { value: 'medium', label: _('Medium'), glyph: '1.25rem' },
    { value: 'large', label: _('Large'), glyph: '1.6rem' },
  ];

  const schemes: { value: MantineColorScheme; label: string; icon: Icon }[] = [
    { value: 'light', label: _('Light'), icon: IconSun },
    { value: 'dark', label: _('Dark'), icon: IconMoon },
    { value: 'auto', label: _('Automatic'), icon: IconDeviceDesktop },
  ];

  return (
    <Stack gap="lg">
      <div>
        <Text fw={650} mb="xs">
          {_('Text size')}
        </Text>
        <SimpleGrid cols={3}>
          {sizes.map((item) => (
            <Option
              key={item.value}
              active={textSize === item.value}
              onClick={() => setTextSize(item.value)}
            >
              <span className="text-size-glyph" style={{ fontSize: item.glyph }} aria-hidden>
                A
              </span>
              {item.label}
            </Option>
          ))}
        </SimpleGrid>
      </div>
      <div>
        <Text fw={650} mb="xs">
          {_('Light or dark')}
        </Text>
        <SimpleGrid cols={{ base: 1, sm: 3 }}>
          {schemes.map((item) => (
            <Option
              key={item.value}
              active={colorScheme === item.value}
              onClick={() => setColorScheme(item.value)}
            >
              <item.icon size={20} /> {item.label}
            </Option>
          ))}
        </SimpleGrid>
      </div>
      <div>
        <Text fw={650} mb="xs">
          {_('Colours')}
        </Text>
        <SimpleGrid cols={{ base: 2, sm: 4 }}>
          {PALETTES.map((item) => (
            <Option key={item.id} active={palette === item.id} onClick={() => setPalette(item.id)}>
              <span className="palette-swatch" aria-hidden>
                <span style={{ background: item.brand[6] }} />
                <span style={{ background: item.accent[5] }} />
              </span>
              {_(item.label)}
            </Option>
          ))}
        </SimpleGrid>
      </div>
      <div>
        <Text fw={650} mb="xs">
          {_('Contrast')}
        </Text>
        <Option active={highContrast} onClick={() => setHighContrast(!highContrast)}>
          <IconContrast size={20} />
          <span>{_('High contrast')}</span>
          <Text className="ds-caption" component="span">
            {_('Stronger lines and text, no transparency.')}
          </Text>
        </Option>
      </div>
    </Stack>
  );
}
