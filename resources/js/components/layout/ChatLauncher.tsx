import { Chat } from '@/components/domain/Chat';
import { minimizeChat, openChat, useChat, useChatProject } from '@/lib/chat';
import { sendJson } from '@/lib/json';
import { route, routeIs } from '@/lib/route';
import { AUTO_AFTER_MS, drivenBrowser, offerTourInstead } from '@/lib/tour';
import { _, _n } from '@/lib/translations';
import { Transition } from '@mantine/core';
import { IconChevronDown, IconMessageCircle } from '@tabler/icons-react';
import { useCallback, useEffect, useRef } from 'react';

/** Once a visit, whatever the server's word on the next page */
let welcomed = false;

/**
 * The project's chat wherever one is, on tablets and up: a bubble at the bottom right corner,
 * and the window that opens from it and folds back into it, without leaving the page. On
 * phones the chat is one more place of the menu, and its own page has it there.
 */
export function ChatLauncher() {
  const { project, prop } = useChatProject();
  const { open } = useChat();
  const here = routeIs('chat.*');
  const welcome = prop === 'project' ? project?.welcome : null;
  const projectId = project?.id;
  const launcher = useRef<HTMLButtonElement>(null);
  const chatWindow = useRef<HTMLElement>(null);

  // Opening takes the focus into the window, unless someone is at work elsewhere…
  const windowOpened = useCallback((element: HTMLElement | null) => {
    chatWindow.current = element;
    const active = document.activeElement;
    if (element && (active === launcher.current || active === document.body)) {
      element.focus({ preventScroll: true });
    }
  }, []);

  // …and folding it back takes it to the bubble, rather than nowhere
  useEffect(() => {
    if (!open && chatWindow.current?.contains(document.activeElement)) launcher.current?.focus();
  }, [open]);

  // Someone's first arrival: the Assistant greets them, and offers the tour the page would play
  useEffect(() => {
    if (!welcome || !projectId || here || welcomed || drivenBrowser()) return undefined;
    welcomed = true;
    offerTourInstead();
    void sendJson('POST', route('chat.welcomed', { project: projectId }), {});
    const later = window.setTimeout(() => openChat(welcome), AUTO_AFTER_MS);
    return () => window.clearTimeout(later);
  }, [welcome, here, projectId]);

  useEffect(() => {
    if (!open) return undefined;
    const escape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && !document.querySelector('[role="dialog"]')) minimizeChat();
    };
    window.addEventListener('keydown', escape);
    return () => window.removeEventListener('keydown', escape);
  }, [open]);

  if (!project?.can.chat || here) return null;

  const unread = project.unread_messages ?? 0;

  return (
    <>
      <Transition mounted={open} transition="pop-bottom-right" duration={220} keepMounted={false}>
        {(styles) => (
          <section
            ref={windowOpened}
            tabIndex={-1}
            className="chat-window ds-surface"
            style={styles}
            aria-label={_('Messages')}
          >
            <Chat key={project.id} popup />
          </section>
        )}
      </Transition>
      <button
        ref={launcher}
        type="button"
        className="chat-launcher"
        aria-expanded={open}
        aria-label={
          open
            ? _('Minimize')
            : unread
              ? _n(
                  'Messages: {count} conversation with something new',
                  'Messages: {count} conversations with something new',
                  unread,
                )
              : _('Messages')
        }
        onClick={() => (open ? minimizeChat() : openChat())}
      >
        {open ? <IconChevronDown size={24} /> : <IconMessageCircle size={24} />}
        {unread && !open ? <span className="chat-launcher-count">{unread}</span> : null}
      </button>
    </>
  );
}
