import { showConversation, useChat, useChatProject, useChatRoute } from '@/lib/chat';
import { getJson } from '@/lib/json';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ChatData, ChatPerson } from './types';

/** Earlier messages come this many at a time */
const MORE = 50;

/** What was last seen, per project: moving between pages, the window opens on it at once */
const seen = new Map<number, ChatData>();

/**
 * What the chat shows, asked of the server on its own (it never leaves the page it is on):
 * the conversations and the one open, asked again whenever something says it changed, and
 * whatever counts the unread brought up to date with it
 */
export function useChatPanel(initial?: ChatData) {
  const { project, account, prop } = useChatProject();
  const chatRoute = useChatRoute();
  // What the bubble counts came with the page; the server's latest answer, how many are unread
  const counted = (project ? project.unread_messages : account?.unread) ?? 0;
  const [unread, setUnread] = useState<number | null>(null);
  // What it remembers is kept per project; 0 is the person's own, outside any
  const projectId = project?.id ?? 0;
  const { conversation: open, version } = useChat();
  const [data, setData] = useState<ChatData | null>(initial ?? seen.get(projectId) ?? null);
  // How many messages were asked for, of the conversation they were asked for
  const [asking, setAsking] = useState({ conversation: open, count: MORE });
  const count = asking.conversation === open ? asking.count : MORE;
  // The latest ask wins: a slow answer never replaces a newer one
  const asked = useRef(0);
  // Whom one may talk with comes once a window opens, not with every message
  const peopleOf = useRef<number | null>(null);

  const load = useCallback(
    async (messages = count) => {
      const ask = ++asked.current;
      const response = await getJson(
        chatRoute('panel', {
          ...(open ? { conversation: open, count: messages } : {}),
          ...(peopleOf.current === projectId ? { known_people: 1 } : {}),
        }),
      );
      // Gone (they left it, or it was never theirs): back to the list
      if (response.status === 404 || response.status === 403) {
        showConversation(null);
        return;
      }
      // No connection, or a newer ask: what shows stays
      if (!response.ok || ask !== asked.current) return;
      const answer = (await response.json()) as Omit<ChatData, 'people'> & {
        people?: ChatPerson[];
      };
      const next = { ...answer, people: answer.people ?? seen.get(projectId)?.people ?? [] };
      peopleOf.current = projectId;
      seen.set(projectId, next);
      setData(next);
      setUnread(next.conversations.filter((one) => one.unread).length);
    },
    [projectId, open, count, chatRoute],
  );

  useEffect(() => {
    void load();
  }, [load, version]);

  // Read since the page came, here or elsewhere: whatever counts them follows the latest answer
  useEffect(() => {
    if (unread !== null && unread !== counted) router.reload({ only: [prop] });
  }, [unread, counted, prop]);

  /** The open conversation's earlier messages too */
  const earlier = async () => {
    const more = (data?.messages?.data.length ?? 0) + MORE;
    setAsking({ conversation: open, count: more });
    await load(more);
  };

  return { data, open, load, earlier };
}
