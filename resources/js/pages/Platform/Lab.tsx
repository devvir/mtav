import { PageHeader } from '@/components/layout/PageHeader';
import { PALETTES, useAppearance } from '@/design';
import '@/lab/lab.css';
import { holdStates } from '@/lab/pseudo';
import { SECTIONS } from '@/lab/sections';
import { _ } from '@/lib/translations';
import {
  SegmentedControl,
  Select,
  Switch,
  useMantineColorScheme,
  type MantineColorScheme,
} from '@mantine/core';
import { useEffect, useState } from 'react';

/**
 * The UI Lab: the design system's components, each in its states and variants, drawn by
 * the palette, scheme and contrast chosen at the top.
 */
export default function Lab() {
  const [section, setSection] = useState(SECTIONS[0].id);
  const { colorScheme, setColorScheme } = useMantineColorScheme();
  const { palette, setPalette, highContrast, setHighContrast } = useAppearance();
  const Section = SECTIONS.find((item) => item.id === section)!.render;

  // Hover, pressed and focus, held still in the cells that show them
  useEffect(() => holdStates(), []);

  return (
    <>
      <PageHeader
        title={_('UI Lab')}
        description={_('Every component of the design system, in its states and variants.')}
      />
      <div className="lab-head">
        <Select
          aria-label={_('Colours')}
          value={palette}
          onChange={(value) => value && setPalette(value)}
          data={PALETTES.map((item) => ({ value: item.id, label: _(item.label) }))}
          allowDeselect={false}
          w={170}
        />
        <SegmentedControl
          value={colorScheme}
          onChange={(value) => setColorScheme(value as MantineColorScheme)}
          data={[
            { value: 'light', label: _('Light') },
            { value: 'dark', label: _('Dark') },
            { value: 'auto', label: _('As my device') },
          ]}
        />
        <Switch
          label={_('High contrast')}
          checked={highContrast}
          onChange={(event) => setHighContrast(event.currentTarget.checked)}
        />
      </div>
      <div className="lab-body" id="lab">
        <nav className="lab-nav" aria-label={_('Sections')}>
          {SECTIONS.map((item) => (
            <button
              key={item.id}
              type="button"
              aria-current={item.id === section || undefined}
              onClick={() => setSection(item.id)}
            >
              {item.label}
            </button>
          ))}
        </nav>
        <div style={{ minWidth: 0 }}>
          <Section />
        </div>
      </div>
    </>
  );
}
