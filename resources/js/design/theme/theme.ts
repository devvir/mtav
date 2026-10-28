import {
  createTheme,
  DEFAULT_THEME,
  type CSSVariablesResolver,
  type MantineThemeOverride,
} from '@mantine/core';
import { mantineVariables, schemeColours, type SchemeColours } from './contrast';
import type { Palette } from './palettes';

/**
 * The Mantine theme for a palette: type, radii, shadows and how the parts behave are
 * MTAV's whatever the palette; colours come from it.
 */

/**
 * While the fonts load, or if they never do: the fonts devices use for their own
 * interfaces, closest in shape to Figtree, then the ones every system has.
 */
const SYSTEM = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

/** A dialog's title never runs into its close button */
const DIALOG_HEADER = { gap: 24, alignItems: 'flex-start' };
const DIALOG_TITLE = {
  fontFamily: 'var(--mantine-font-family-headings)',
  fontWeight: 700,
  fontSize: 'var(--mantine-font-size-xl)',
  lineHeight: 1.25,
};
/** Behind a dialog or a drawer the page stays in view, blurred */
const OVERLAY = { blur: 6, backgroundOpacity: 0.55 };

export const themeFor = (palette: Palette): MantineThemeOverride =>
  createTheme({
    primaryColor: 'brand',
    primaryShade: { light: 7, dark: 5 },
    // Filled colours are chosen so that white reads on them (contrast.ts): text on them is white
    autoContrast: false,
    // Mantine's dark greys draw its own parts (fields, menus, dialogs): set to MTAV's, from
    // text (0) through borders (4) and raised surfaces (6) to the page (9)
    colors: {
      brand: palette.brand,
      accent: palette.accent,
      dark: [
        '#eeece9',
        '#c9c4be',
        '#a39c94',
        '#837b73',
        '#3d3832',
        '#2e2a26',
        '#211e1b',
        '#1a1715',
        '#151210',
        palette.canvas.dark,
      ],
    },
    fontFamily: `"Figtree Variable", ${SYSTEM}`,
    headings: {
      fontFamily: `"Bricolage Grotesque Variable", "Figtree Variable", ${SYSTEM}`,
      fontWeight: '700',
    },
    defaultRadius: 'md',
    radius: { xs: '6px', sm: '8px', md: '12px', lg: '16px', xl: '24px' },
    shadows: {
      xs: '0 1px 2px rgb(20 15 10 / 6%)',
      sm: '0 2px 8px rgb(20 15 10 / 8%)',
      md: '0 8px 24px rgb(20 15 10 / 10%)',
      lg: '0 16px 40px rgb(20 15 10 / 14%)',
      xl: '0 24px 64px rgb(20 15 10 / 18%)',
    },
    cursorType: 'pointer',
    components: {
      Button: { defaultProps: { radius: 'xl' }, classNames: { root: 'ds-button' } },
      ActionIcon: { defaultProps: { radius: 'md', variant: 'subtle' } },
      Badge: {
        defaultProps: { radius: 'xl', variant: 'light' },
        classNames: { root: 'ds-badge' },
        styles: { root: { textTransform: 'none', fontWeight: 650 } },
      },
      Card: {
        defaultProps: { radius: 'lg', withBorder: true },
        classNames: { root: 'ds-surface' },
      },
      Paper: { defaultProps: { radius: 'lg' } },
      Modal: {
        defaultProps: {
          radius: 'lg',
          centered: true,
          overlayProps: OVERLAY,
          removeScrollProps: { removeScrollBar: false },
        },
        styles: { title: DIALOG_TITLE, header: DIALOG_HEADER },
      },
      Drawer: {
        defaultProps: { overlayProps: OVERLAY, removeScrollProps: { removeScrollBar: false } },
        styles: { title: DIALOG_TITLE, header: DIALOG_HEADER },
      },
      // A field's hint beside its label, never under it: fields side by side stay in line
      // whether or not they have one (styles.css)
      InputWrapper: {
        classNames: { root: 'ds-field', label: 'ds-field-label', description: 'ds-field-hint' },
      },
      // Menus and popovers: raised above the page, with room inside and rounded items
      Menu: {
        defaultProps: {
          radius: 'lg',
          shadow: 'md',
          offset: 8,
          transitionProps: { transition: 'pop-top-right', duration: 140 },
        },
        classNames: { dropdown: 'ds-floating', item: 'ds-menu-item', label: 'ds-panel-label' },
      },
      Popover: {
        defaultProps: {
          radius: 'lg',
          shadow: 'md',
          offset: 8,
          transitionProps: { transition: 'pop', duration: 140 },
        },
        classNames: { dropdown: 'ds-floating' },
      },
      Combobox: {
        defaultProps: { radius: 'lg', shadow: 'md' },
        classNames: { dropdown: 'ds-floating', option: 'ds-menu-item' },
      },
      // The chosen option is marked by its colour and weight, never a tick that pushes its
      // label out of line
      Select: { defaultProps: { withCheckIcon: false } },
      MultiSelect: { defaultProps: { withCheckIcon: false } },
      Tooltip: { defaultProps: { withArrow: true, openDelay: 300 } },
      Tabs: { classNames: { list: 'ds-tabs-list' } },
      SegmentedControl: { defaultProps: { radius: 'xl' }, classNames: { root: 'ds-segmented' } },
      Table: { classNames: { table: 'ds-table', th: 'ds-th' } },
      Notification: { defaultProps: { radius: 'lg' }, classNames: { root: 'ds-floating' } },
    },
  });

/**
 * MTAV's tokens, as CSS variables, for light and dark: what every page is drawn with.
 * `--mtav-*` only; pages never name a colour. High contrast drops what is translucent or
 * glows, and draws lines and quiet text at full strength.
 */
export const tokensFor = (palette: Palette, highContrast: boolean): CSSVariablesResolver => {
  // Every colour's parts, by measured contrast, in each scheme (contrast.ts)
  const { dark: _greys, ...defaults } = DEFAULT_THEME.colors;
  const all = { ...defaults, brand: palette.brand, accent: palette.accent };
  const darkSurface = highContrast ? '#000000' : '#1a1715';
  const colours = (scheme: 'light' | 'dark') =>
    Object.fromEntries(
      Object.entries(all).map(([name, tuple]) => [
        name,
        schemeColours(tuple, scheme, scheme === 'light' ? '#ffffff' : darkSurface),
      ]),
    ) as Record<string, SchemeColours>;
  const light = colours('light');
  const dark = colours('dark');
  const variables = (set: Record<string, SchemeColours>) =>
    Object.assign({}, ...Object.entries(set).map(([name, parts]) => mantineVariables(name, parts)));

  return () => ({
    variables: {
      '--mtav-radius-card': '16px',
    },
    light: {
      ...variables(light),
      '--mtav-canvas': palette.canvas.light,
      '--mtav-surface': highContrast ? '#ffffff' : 'rgb(255 255 255 / 78%)',
      '--mtav-surface-2': highContrast ? '#ffffff' : 'rgb(255 255 255 / 92%)',
      '--mtav-surface-solid': '#ffffff',
      '--mtav-surface-raised': '#ffffff',
      '--mtav-hover': 'rgb(20 15 10 / 4%)',
      '--mtav-header': highContrast ? '#ffffff' : 'rgb(255 255 255 / 72%)',
      '--mtav-border': highContrast ? '#3d3832' : 'rgb(40 25 15 / 10%)',
      '--mtav-border-strong': highContrast ? '#14110f' : 'rgb(40 25 15 / 18%)',
      '--mtav-shadow': highContrast
        ? 'none'
        : 'inset 0 1px 0 rgb(255 255 255 / 60%), 0 10px 30px -18px rgb(60 35 15 / 35%)',
      '--mtav-text': '#14110f',
      '--mtav-muted': highContrast ? '#14110f' : '#5e5751',
      '--mantine-color-dimmed': highContrast ? '#14110f' : '#5e5751',
      // The brand and the tones as text and lines: shades that read on their own tints
      '--mtav-brand': light.brand!.text,
      '--mtav-on-brand': '#ffffff',
      '--mtav-brand-soft': light.brand!.light,
      '--mtav-accent': light.accent!.text,
      '--mtav-accent-soft': light.accent!.light,
      '--mtav-positive': light.green!.text,
      '--mtav-warning': light.yellow!.text,
      '--mtav-negative': light.red!.text,
      '--mantine-color-error': light.red!.text,
      '--mtav-info': light.blue!.text,
      '--mtav-glow': highContrast
        ? 'none'
        : `radial-gradient(1100px 520px at 8% -10%, color-mix(in srgb, ${palette.accent[3]} 22%, transparent), transparent 70%), radial-gradient(900px 480px at 95% -14%, color-mix(in srgb, ${palette.brand[3]} 26%, transparent), transparent 70%)`,
    },
    dark: {
      ...variables(dark),
      '--mtav-canvas': highContrast ? '#000000' : palette.canvas.dark,
      '--mtav-surface': highContrast ? '#000000' : 'rgb(255 255 255 / 2.5%)',
      '--mtav-surface-2': highContrast ? '#000000' : 'rgb(255 255 255 / 5%)',
      '--mtav-surface-solid': highContrast ? '#000000' : '#1a1715',
      '--mtav-surface-raised': highContrast ? '#000000' : '#211e1b',
      '--mtav-hover': 'rgb(255 255 255 / 4%)',
      '--mtav-header': highContrast
        ? '#000000'
        : `color-mix(in srgb, ${palette.canvas.dark} 74%, transparent)`,
      '--mtav-border': highContrast ? '#c9c4be' : 'rgb(255 255 255 / 8%)',
      '--mtav-border-strong': highContrast ? '#ffffff' : 'rgb(255 255 255 / 14%)',
      '--mtav-shadow': highContrast
        ? 'none'
        : 'inset 0 1px 0 rgb(255 255 255 / 3%), 0 10px 30px -18px rgb(0 0 0 / 80%)',
      '--mtav-text': highContrast ? '#ffffff' : '#eeece9',
      '--mtav-muted': highContrast ? '#ffffff' : '#a39c94',
      '--mantine-color-dimmed': highContrast ? '#ffffff' : '#a39c94',
      '--mtav-brand': dark.brand!.text,
      '--mtav-on-brand': '#ffffff',
      '--mtav-brand-soft': dark.brand!.light,
      '--mtav-accent': dark.accent!.text,
      '--mtav-accent-soft': dark.accent!.light,
      '--mtav-positive': dark.green!.text,
      '--mtav-warning': dark.yellow!.text,
      '--mtav-negative': dark.red!.text,
      '--mantine-color-error': dark.red!.text,
      '--mtav-info': dark.blue!.text,
      '--mtav-glow': highContrast
        ? 'none'
        : `radial-gradient(1100px 520px at 8% -10%, color-mix(in srgb, ${palette.accent[6]} 10%, transparent), transparent 70%), radial-gradient(900px 480px at 95% -14%, color-mix(in srgb, ${palette.brand[6]} 12%, transparent), transparent 70%)`,
    },
  });
};
