import { Glyph } from '@/design';
import { Text, Title } from '@mantine/core';
import type { Icon } from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** The head of a sign-in card: an icon, what the page is, a line on it */
export function AuthHeading({
  icon,
  title,
  children,
}: {
  icon?: Icon;
  title: string;
  children?: ReactNode;
}) {
  return (
    <div style={{ marginBottom: 24 }}>
      {icon ? (
        <div style={{ marginBottom: 16 }}>
          <Glyph icon={icon} size={48} />
        </div>
      ) : null}
      <Title order={1} fz={26}>
        {title}
      </Title>
      {children ? (
        <Text c="dimmed" mt={6}>
          {children}
        </Text>
      ) : null}
    </div>
  );
}
