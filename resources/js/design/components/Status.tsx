import type { ReactNode } from 'react';
import { toneStyle, type Tone } from '../tone';

/** A state: a dot in its tone and its name; what is happening now pulses */
export function Status({
  tone,
  live,
  pill,
  children,
}: {
  tone: Tone;
  live?: boolean;
  pill?: boolean;
  children: ReactNode;
}) {
  return (
    <span className="ds-status" data-pill={pill || undefined} style={toneStyle(tone)}>
      <span className="ds-dot" data-live={live || undefined} aria-hidden />
      <span>{children}</span>
    </span>
  );
}
