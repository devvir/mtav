import { Avatar, Image, Modal, UnstyledButton, type AvatarProps } from '@mantine/core';
import { useState } from 'react';

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

/**
 * A person: their photo, or their initials on their colour. Given `expandable` (what its
 * button says), a photo opens large when clicked, as on a social network (initials have
 * nothing more to show).
 */
export function PersonAvatar({
  name,
  src,
  expandable,
  ...rest
}: { name: string; src?: string | null; expandable?: string } & AvatarProps) {
  const [open, setOpen] = useState(false);
  const avatar = (
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
  if (!expandable || !src) return avatar;

  return (
    <>
      <UnstyledButton
        className="avatar-expand"
        aria-label={expandable}
        onClick={() => setOpen(true)}
      >
        {avatar}
      </UnstyledButton>
      <Modal opened={open} onClose={() => setOpen(false)} title={name} centered size="auto">
        <Image src={src} alt={name} radius="md" className="avatar-large" />
      </Modal>
    </>
  );
}
