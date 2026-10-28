import { hueOf } from './PersonAvatar';

/**
 * A family's mark: a pattern grown from its name (five by five, mirrored), always the same
 * for the same name, in its own colour. Families are told apart at a glance.
 */
/** The cells of a name's pattern: the left three columns drawn by a seeded generator, mirrored */
function patternOf(name: string): [number, number][] {
  let seed = [...name].reduce((hash, char) => (hash * 33 + char.charCodeAt(0)) >>> 0, 5381);
  const cells: [number, number][] = [];

  for (let row = 0; row < 5; row++) {
    for (let col = 0; col < 3; col++) {
      seed = (seed * 1103515245 + 12345) >>> 0;
      if (seed % 2) {
        cells.push([col, row]);
        if (col < 2) cells.push([4 - col, row]);
      }
    }
  }

  return cells;
}

export function FamilyMark({ name, size = 40 }: { name: string; size?: number }) {
  const cells = patternOf(name);
  const hue = hueOf(name);

  return (
    <svg
      width={size}
      height={size}
      viewBox="-1 -1 7 7"
      role="img"
      aria-label={name}
      className="ds-family-mark"
      style={{ background: `hsl(${hue} 60% 50% / 14%)` }}
    >
      {cells.map(([x, y]) => (
        <rect key={`${x}-${y}`} x={x} y={y} width={1} height={1} fill={`hsl(${hue} 62% 46%)`} />
      ))}
    </svg>
  );
}
