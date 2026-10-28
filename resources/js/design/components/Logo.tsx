import { IconBuildingCommunity } from '@tabler/icons-react';

/**
 * MTAV's mark — a tile in the brand's colours — with or without the name, and the line
 * naming the institution behind the deployment, if there is one
 */
export function Logo({
  name,
  institution,
  size = 34,
}: {
  name?: string;
  institution?: string | null;
  size?: number;
}) {
  return (
    <span className="ds-logo">
      <span className="ds-logo-mark" style={{ width: size, height: size }} aria-hidden>
        <IconBuildingCommunity size={Math.round(size * 0.6)} stroke={1.9} />
      </span>
      {name ? (
        <span className="ds-logo-words">
          <span className="ds-logo-name">{name}</span>
          {institution ? <span className="ds-logo-institution">{institution}</span> : null}
        </span>
      ) : null}
    </span>
  );
}
