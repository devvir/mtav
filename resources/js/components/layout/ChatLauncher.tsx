import { route, routeIs } from '@/lib/route';
import { _, _n } from '@/lib/translations';
import { Link, usePage } from '@inertiajs/react';
import { IconMessageCircle } from '@tabler/icons-react';

/**
 * The project's chat a click away, at the bottom corner of the page, on tablets and up (on
 * phones it is one more place of the menu). Not over the chat itself.
 */
export function ChatLauncher() {
  const project = usePage().props.project;
  if (!project?.can.chat || routeIs('chat.*')) return null;

  const unread = project.unread_messages ?? 0;

  return (
    <Link
      href={route('chat.index', { project: project.id })}
      className="chat-launcher"
      aria-label={
        unread
          ? _n(
              'Messages: {count} conversation with something new',
              'Messages: {count} conversations with something new',
              unread,
            )
          : _('Messages')
      }
    >
      <IconMessageCircle size={24} />
      {unread ? <span className="chat-launcher-count">{unread}</span> : null}
    </Link>
  );
}
