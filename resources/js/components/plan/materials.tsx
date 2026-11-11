/**
 * The materials every shape on a plan draws from (light and shade, grass, asphalt, canopies,
 * play equipment, the commons' hall), lit from the top left. They are SVG defs: each plan
 * draws them once, their ids carrying its own prefix.
 */

/** Canopies seen from above: crowns as [x, y, radius] in a 20 × 20 box */
const TREES: [number, number, number][][] = [
  [
    [-3, -2, 5.5],
    [3, -3, 5],
    [2, 3, 5.5],
    [-3.5, 3.5, 4.5],
    [0, 0, 5],
  ],
  [
    [-2.5, -2.5, 6],
    [3.5, 0, 5.2],
    [-1, 3.8, 5],
  ],
  [
    [0, -4, 4.5],
    [-4, 0, 4.5],
    [4, 0.5, 4.5],
    [0, 4, 4.5],
    [0, 0, 5.5],
  ],
];

/** The materials every shape on a plan draws from; ids carry the plan's own prefix */
export function PlanDefs({ id }: { id: string }) {
  return (
    <defs>
      {/* Light from the top left: what stands casts its shade to the bottom right */}
      <filter id={`${id}-shadow`} x="-25%" y="-25%" width="160%" height="160%">
        <feDropShadow className="plan-shade" dx="3" dy="4" stdDeviation="3" />
      </filter>
      <filter id={`${id}-ground-shadow`} x="-15%" y="-15%" width="130%" height="140%">
        <feDropShadow className="plan-shade-low" dx="1" dy="1.5" stdDeviation="1.5" />
      </filter>
      <filter id={`${id}-soft`} x="-30%" y="-30%" width="160%" height="160%">
        <feGaussianBlur stdDeviation="1.2" />
      </filter>
      {/* Light from the top left, laid over any colour */}
      <linearGradient id={`${id}-light`} x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stopColor="#fff" stopOpacity="0.45" />
        <stop offset="0.45" stopColor="#fff" stopOpacity="0.06" />
        <stop offset="1" stopColor="#000" stopOpacity="0.14" />
      </linearGradient>
      {/* Three greens of canopy, each lit from the top left */}
      {[
        ['#a3d977', '#5b9a3c', '#3d7429'],
        ['#8fcf86', '#4f9a52', '#2f6b3a'],
        ['#b7d98a', '#73a34a', '#4c7a2e'],
      ].map(([light, mid, dark], kind) => (
        <radialGradient key={kind} id={`${id}-canopy${kind}`} cx="0.35" cy="0.3" r="0.8">
          <stop offset="0" stopColor={light} />
          <stop offset="0.6" stopColor={mid} />
          <stop offset="1" stopColor={dark} />
        </radialGradient>
      ))}
      <pattern id={`${id}-land`} width="16" height="16" patternUnits="userSpaceOnUse">
        <rect width="16" height="16" fill="var(--plan-land)" />
        <circle cx="3" cy="4" r="0.7" fill="var(--plan-land-grain)" />
        <circle cx="11" cy="9" r="0.6" fill="var(--plan-land-grain)" />
        <circle cx="6" cy="13" r="0.5" fill="var(--plan-land-grain)" />
      </pattern>
      <pattern id={`${id}-grass`} width="12" height="12" patternUnits="userSpaceOnUse">
        <rect width="12" height="12" fill="#9bc97f" />
        <path
          d="M2 8 l0.8 -2.6 M6.5 10.5 l0.9 -2.6 M9 4.5 l0.8 -2.4 M4 3.5 l-0.8 -2 M10.5 9 l-0.7 -2"
          stroke="#6f9f56"
          strokeWidth="0.7"
          strokeLinecap="round"
          opacity="0.8"
        />
        <circle cx="7.5" cy="6.5" r="0.6" fill="#c8e3a8" />
      </pattern>
      <pattern id={`${id}-asphalt`} width="12" height="12" patternUnits="userSpaceOnUse">
        <rect width="12" height="12" fill="#57534e" />
        <circle cx="2" cy="3" r="0.6" fill="#78716c" />
        <circle cx="8" cy="5" r="0.5" fill="#44403c" />
        <circle cx="5" cy="10" r="0.6" fill="#78716c" />
        <circle cx="10" cy="11" r="0.4" fill="#a8a29e" />
      </pattern>
      <pattern
        id={`${id}-stripes`}
        width="8"
        height="8"
        patternUnits="userSpaceOnUse"
        patternTransform="rotate(45)"
      >
        <rect width="8" height="8" fill="transparent" />
        <rect width="3" height="8" fill="#fff" opacity="0.35" />
      </pattern>
      <pattern id={`${id}-play`} width="14" height="14" patternUnits="userSpaceOnUse">
        <rect width="14" height="14" fill="#fb923c" />
        <circle cx="4" cy="4" r="1.6" fill="#fdba74" />
        <circle cx="11" cy="10" r="1.3" fill="#fed7aa" />
      </pattern>
      {/* A place for gatherings, from above: a one-floor hall with a hip roof, its garden,
          trees and benches, a path to its door; drawn once, as large as the space */}
      <symbol id={`${id}-commons`} viewBox="0 0 100 70" preserveAspectRatio="xMidYMid meet">
        <rect x="2" y="2" width="96" height="66" rx="6" fill="#86efac" opacity="0.45" />
        <path d="M34 50 V68" stroke="#e7d9bf" strokeWidth="5" strokeLinecap="round" />
        <rect
          x="10"
          y="12"
          width="50"
          height="38"
          rx="1.5"
          fill="#1c1917"
          opacity="0.18"
          transform="translate(1.6 2)"
        />
        <rect x="10" y="12" width="50" height="38" rx="1.5" fill="#e08a5f" />
        <path
          d="M10 12 L24 31 L46 31 L60 12 M10 50 L24 31 M60 50 L46 31"
          stroke="#b45f3c"
          strokeWidth="1.1"
          fill="none"
        />
        <path d="M10 12 L24 31 L10 50 Z" fill="#c9714a" opacity="0.6" />
        <path d="M60 12 L46 31 L60 50 Z" fill="#f3a57c" opacity="0.5" />
        <rect x="31" y="48" width="6" height="3" fill="#7c4a2f" />
        {[
          [76, 18, 7],
          [88, 30, 6],
          [74, 44, 6.5],
        ].map(([cx, cy, r], index) => (
          <g key={index}>
            <circle cx={cx! + 1.5} cy={cy! + 2} r={r} fill="#1f3d17" opacity="0.25" />
            <circle cx={cx} cy={cy} r={r} fill={`url(#${id}-canopy${index % 3})`} />
          </g>
        ))}
        <rect x="64" y="56" width="10" height="2.6" rx="1" fill="#8b5e3c" />
        <rect x="80" y="56" width="10" height="2.6" rx="1" fill="#8b5e3c" />
      </symbol>
      {/* Play equipment from above, in bright paint, with a little shade */}
      <symbol id={`${id}-play-swing`} viewBox="-10 -10 20 20" overflow="visible">
        <g className="plan-play-shade">
          <rect x="-8.5" y="-1" width="17" height="2.4" rx="1" />
        </g>
        <rect x="-9" y="-1.6" width="18" height="2.2" rx="1" fill="#475569" />
        <circle cx="-9" cy="-0.5" r="1.6" fill="#334155" />
        <circle cx="9" cy="-0.5" r="1.6" fill="#334155" />
        <path d="M-4.5 0.6 V5.5 M4.5 0.6 V5.5" stroke="#64748b" strokeWidth="0.6" />
        <rect x="-6" y="5" width="3" height="2.2" rx="0.6" fill="#ef4444" />
        <rect x="3" y="5" width="3" height="2.2" rx="0.6" fill="#3b82f6" />
      </symbol>
      <symbol id={`${id}-play-slide`} viewBox="-10 -10 20 20" overflow="visible">
        <g className="plan-play-shade">
          <rect x="-3" y="-8" width="7" height="18" rx="2.5" />
        </g>
        <rect x="-4" y="-9" width="8" height="6" rx="1" fill="#f59e0b" />
        <path d="M-3 -8 H3 M-3 -6 H3 M-3 -4 H3" stroke="#b45309" strokeWidth="0.6" />
        <rect x="-2.6" y="-3.4" width="5.2" height="12" rx="2.6" fill="#22c55e" />
        <rect x="-1.4" y="-2.6" width="2.8" height="10.6" rx="1.4" fill="#86efac" />
      </symbol>
      <symbol id={`${id}-play-sandpit`} viewBox="-10 -10 20 20" overflow="visible">
        <g className="plan-play-shade">
          <rect x="-7" y="-6" width="15" height="15" rx="3" />
        </g>
        <rect x="-8" y="-8" width="16" height="16" rx="3" fill="#a16207" />
        <rect x="-6.4" y="-6.4" width="12.8" height="12.8" rx="2" fill="#fde68a" />
        <circle cx="-2" cy="-1.5" r="1.4" fill="#fcd34d" />
        <circle cx="2.5" cy="2" r="1" fill="#fbbf24" />
        <path d="M1 -4 l2.6 1.5" stroke="#dc2626" strokeWidth="1" strokeLinecap="round" />
      </symbol>
      <symbol id={`${id}-play-roundabout`} viewBox="-10 -10 20 20" overflow="visible">
        <g className="plan-play-shade">
          <circle cx="1.5" cy="2" r="8" />
        </g>
        <circle r="8" fill="#8b5cf6" />
        <path
          d="M0 -8 V8 M-8 0 H8 M-5.7 -5.7 L5.7 5.7 M-5.7 5.7 L5.7 -5.7"
          stroke="#ede9fe"
          strokeWidth="0.9"
        />
        <circle r="2" fill="#facc15" />
      </symbol>
      {/* A tree from above: its shade on the ground, then a canopy of a few crowns */}
      {TREES.map((crowns, kind) => (
        <symbol key={kind} id={`${id}-tree${kind}`} viewBox="-10 -10 20 20" overflow="visible">
          <g transform="translate(2.6 3.2)" filter={`url(#${id}-soft)`} opacity="0.35">
            {crowns.map(([x, y, r], index) => (
              <circle key={index} cx={x} cy={y} r={r} fill="#1f3d17" />
            ))}
          </g>
          {crowns.map(([x, y, r], index) => (
            <circle
              key={index}
              cx={x}
              cy={y}
              r={r}
              fill={`url(#${id}-canopy${kind})`}
              stroke="#2c5221"
              strokeWidth="0.5"
              strokeOpacity="0.45"
            />
          ))}
        </symbol>
      ))}
    </defs>
  );
}
