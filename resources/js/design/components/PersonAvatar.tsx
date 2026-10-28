import { Avatar, type AvatarProps } from '@mantine/core';

/** A stable hue per name, so the same person always has the same colour */
export const hueOf = (name: string) =>
  [...name].reduce((hash, char) => (hash * 31 + char.charCodeAt(0)) % 360, 7);

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0]!.toUpperCase())
    .join('');

/** A person: their photo, or their initials on their colour */
export function PersonAvatar({
  name,
  src,
  ...rest
}: { name: string; src?: string | null } & AvatarProps) {
  return (
    <Avatar
      src={src ?? undefined}
      alt={name}
      radius="xl"
      {...rest}
      styles={{
        root: { background: `hsl(${hueOf(name)} 45% 32%)`, color: '#fff' },
        placeholder: { background: 'transparent', color: '#fff', fontWeight: 700 },
      }}
    >
      {initials(name)}
    </Avatar>
  );
}
