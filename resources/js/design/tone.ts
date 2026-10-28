import type { CSSProperties } from 'react';

/** What a part says by its colour */
export type Tone = 'brand' | 'accent' | 'positive' | 'warning' | 'negative' | 'info' | 'neutral';

const TOKENS: Record<Tone, string> = {
  brand: 'var(--mtav-brand)',
  accent: 'var(--mtav-accent)',
  positive: 'var(--mtav-positive)',
  warning: 'var(--mtav-warning)',
  negative: 'var(--mtav-negative)',
  info: 'var(--mtav-info)',
  neutral: 'var(--mtav-muted)',
};

/** The tone as the `--ds-tone` variable the parts draw with */
export const toneStyle = (tone: Tone, style?: CSSProperties) =>
  ({ '--ds-tone': TOKENS[tone], ...style }) as CSSProperties;

/** Mantine's colour for a tone (badges, buttons, alerts) */
export const MANTINE_COLOR: Record<Tone, string> = {
  brand: 'brand',
  accent: 'accent',
  positive: 'green',
  warning: 'yellow',
  negative: 'red',
  info: 'blue',
  neutral: 'gray',
};
