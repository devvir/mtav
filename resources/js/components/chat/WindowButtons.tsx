import { minimizeChat, useChat, useChatProject, useChatRoute } from '@/lib/chat';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { ActionIcon } from '@mantine/core';
import { IconArrowsMaximize, IconMinus } from '@tabler/icons-react';

/** The window's own buttons: onto the chat's page, at the same conversation, or back into the bubble */
export function WindowButtons() {
  const { project } = useChatProject();
  const chatRoute = useChatRoute();
  const { conversation } = useChat();
  // Outside any project the page opens the Assistant on its own
  const page = project && conversation ? chatRoute('show', { conversation }) : chatRoute('index');
  return (
    <>
      <ActionIcon
        component={Link}
        href={page}
        variant="subtle"
        color="gray"
        aria-label={_('Enlarge')}
        onClick={minimizeChat}
      >
        <IconArrowsMaximize size={18} />
      </ActionIcon>
      <ActionIcon variant="subtle" color="gray" aria-label={_('Minimize')} onClick={minimizeChat}>
        <IconMinus size={18} />
      </ActionIcon>
    </>
  );
}
