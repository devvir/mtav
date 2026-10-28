import { Link } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconAlertTriangle, IconBell, IconCheck, IconInfoCircle, IconX } from '@tabler/icons-react';
import type { ReactNode } from 'react';

export type ToastTone = 'success' | 'info' | 'warning' | 'error';

const LOOK: Record<ToastTone, { color: string; icon: ReactNode }> = {
  success: { color: 'green', icon: <IconCheck size={18} /> },
  info: { color: 'blue', icon: <IconInfoCircle size={18} /> },
  warning: { color: 'yellow', icon: <IconAlertTriangle size={18} /> },
  error: { color: 'red', icon: <IconX size={18} /> },
};

/**
 * Short-lived messages in a corner: flash messages after an action, and notifications
 * arriving live. Errors stay until dismissed.
 */
export function toast({
  tone,
  title,
  message,
  live,
  href,
}: {
  tone: ToastTone;
  title?: string;
  message: ReactNode;
  /** A notification that arrived (a bell rather than the tone's mark) */
  live?: boolean;
  /** Where it leads: the message becomes a link there */
  href?: string | null;
}) {
  notifications.show({
    color: LOOK[tone].color,
    icon: live ? <IconBell size={18} /> : LOOK[tone].icon,
    title,
    message: href ? <Link href={href}>{message}</Link> : message,
    autoClose: tone === 'error' ? false : title ? 8000 : 5000,
  });
}
