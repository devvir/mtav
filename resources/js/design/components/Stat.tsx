import type { Icon } from '@tabler/icons-react';
import type { ReactNode } from 'react';
import { toneStyle, type Tone } from '../tone';
import { Glyph } from './Glyph';

/** A figure that matters, named, in its tone, with a word under it */
export function Stat({
  label,
  value,
  note,
  icon,
  tone = 'brand',
}: {
  label: ReactNode;
  value: ReactNode;
  note?: ReactNode;
  icon?: Icon;
  tone?: Tone;
}) {
  return (
    <div className="ds-stat" style={toneStyle(tone)}>
      <div
        style={{
          display: 'flex',
          alignItems: 'flex-start',
          justifyContent: 'space-between',
          gap: 12,
        }}
      >
        <span className="ds-label">{label}</span>
        {icon ? <Glyph icon={icon} tone={tone} size={34} /> : null}
      </div>
      <div className="ds-figure">{value}</div>
      {note ? (
        <div className="ds-caption" style={{ marginTop: 4 }}>
          {note}
        </div>
      ) : null}
    </div>
  );
}
