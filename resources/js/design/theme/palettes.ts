import { DEFAULT_THEME, type MantineColorsTuple } from '@mantine/core';

/**
 * MTAV's palettes. A palette is one choice; light or dark is another, and high contrast a
 * third: every palette works with both. A new palette is a new entry here and nothing else
 * changes, since pages only ever use the tokens (theme.ts).
 */
export interface Palette {
  id: string;
  /** Its name, in English (the app translates it) */
  label: string;
  /** The brand colour, ten shades from lightest to darkest (Mantine's scale) */
  brand: MantineColorsTuple;
  /** A second colour, for highlights */
  accent: MantineColorsTuple;
  /** What a page is drawn on, light and dark */
  canvas: { light: string; dark: string };
}

const { colors } = DEFAULT_THEME;

export const PALETTES: Palette[] = [
  {
    id: 'terracotta',
    label: 'Terracotta',
    brand: [
      '#fff1e8',
      '#fde1d1',
      '#f8c0a3',
      '#f39d72',
      '#ee7f48',
      '#eb6c2d',
      '#ea621f',
      '#d05113',
      '#ba470e',
      '#a33a06',
    ],
    accent: colors.teal,
    canvas: { light: '#faf7f4', dark: '#120f0d' },
  },
  {
    id: 'indigo',
    label: 'Indigo',
    brand: [
      '#eef0ff',
      '#dde1ff',
      '#b9c0ff',
      '#929dfd',
      '#7280fa',
      '#5c6cf8',
      '#5062f8',
      '#4152dd',
      '#3848c6',
      '#2b3cae',
    ],
    accent: colors.orange,
    canvas: { light: '#f7f8fc', dark: '#0e1017' },
  },
  {
    id: 'forest',
    label: 'Forest',
    brand: [
      '#e6fcf3',
      '#d4f5e8',
      '#aae9d0',
      '#7ddcb6',
      '#58d1a0',
      '#41ca92',
      '#33c68a',
      '#23ae76',
      '#169b68',
      '#008657',
    ],
    accent: colors.grape,
    canvas: { light: '#f6faf8', dark: '#0c1310' },
  },
  {
    id: 'ocean',
    label: 'Ocean',
    brand: colors.cyan,
    accent: colors.pink,
    canvas: { light: '#f5f9fb', dark: '#0b1216' },
  },
];

export const paletteOf = (id: string | null | undefined): Palette =>
  PALETTES.find((one) => one.id === id) ?? PALETTES[0];
