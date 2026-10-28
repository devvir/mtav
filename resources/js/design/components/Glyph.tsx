import type { Icon } from '@tabler/icons-react';
import { toneStyle, type Tone } from '../tone';

/** An icon in its tone, on a tile of it */
export function Glyph({
  icon: Shape,
  tone = 'brand',
  size = 34,
}: {
  icon: Icon;
  tone?: Tone;
  size?: number;
}) {
  return (
    <span className="ds-glyph" style={toneStyle(tone, { width: size, height: size })} aria-hidden>
      <Shape size={Math.round(size * 0.56)} stroke={1.8} />
    </span>
  );
}
