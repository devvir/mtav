import { Chat } from '@/components/domain/Chat';
import { minimizeChat, openChat, useChat, useChatProject, useChatRoute } from '@/lib/chat';
import { sendJson } from '@/lib/json';
import { routeIs } from '@/lib/route';
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
 * phones the chat is one more place of the menu, and its own page has it there. Outside any
 * project, the same for the person's own conversation with the Assistant.
 */
export function ChatLauncher() {
  const { project, account, prop } = useChatProject();
  const chatRoute = useChatRoute();
  const { open } = useChat();
  const here = routeIs('chat.*', 'account.chat.*');
  const welcome = prop === 'project' ? project?.welcome : (account?.welcome ?? null);
  const shown = Boolean(project?.can.chat || account);
  // Each chat is its own: a project's, or (0) the person's own
  const chatKey = project?.id ?? (account ? 0 : undefined);
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
    if (!welcome || here || welcomed || drivenBrowser()) return undefined;
    welcomed = true;
    offerTourInstead();
    void sendJson('POST', chatRoute('welcomed'), {});
    const later = window.setTimeout(() => openChat(welcome), AUTO_AFTER_MS);
    return () => window.clearTimeout(later);
  }, [welcome, here, chatRoute]);

  useEffect(() => {
    if (!open) return undefined;
    const escape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && !document.querySelector('[role="dialog"]')) minimizeChat();
    };
    window.addEventListener('keydown', escape);
    return () => window.removeEventListener('keydown', escape);
  }, [open]);

  if (!shown || here) return null;

  const unread = (project ? project.unread_messages : account?.unread) ?? 0;

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
            <Chat key={chatKey} popup />
          </section>
        )}
      </Transition>
      <button
        ref={launcher}
        type="button"
        className="chat-launcher"
        data-tour="shell.chat"
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
