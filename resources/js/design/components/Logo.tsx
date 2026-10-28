import { IconBuildingCommunity } from '@tabler/icons-react';

/** MTAV's mark — a tile in the brand's colours — with or without the name */
export function Logo({ name, size = 34 }: { name?: string; size?: number }) {
  return (
    <span className="ds-logo">
      <span className="ds-logo-mark" style={{ width: size, height: size }} aria-hidden>
        <IconBuildingCommunity size={Math.round(size * 0.6)} stroke={1.9} />
      </span>
      {name ? <span className="ds-logo-name">{name}</span> : null}
    </span>
  );
}
