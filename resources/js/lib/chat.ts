import { route } from '@/lib/route';
import type { SharedProps } from '@/types/page';
import { usePage } from '@inertiajs/react';
import { useCallback, useSyncExternalStore } from 'react';

/**
 * The chat's window, wherever one is in the app: whether it is open, which conversation it
 * shows, and a count that moves when something new arrives. Kept here, it outlives moving
 * between pages.
 */
interface ChatState {
  open: boolean;
  conversation: number | null;
  version: number;
}

let state: ChatState = { open: false, conversation: null, version: 0 };
const listeners = new Set<() => void>();

const set = (next: Partial<ChatState>) => {
  state = { ...state, ...next };
  listeners.forEach((notify) => notify());
};

export const openChat = (conversation?: number) =>
  set({ open: true, conversation: conversation ?? state.conversation });
export const minimizeChat = () => set({ open: false });
export const showConversation = (conversation: number | null) => set({ conversation });
export const refreshChat = () => set({ version: state.version + 1 });
export const chatState = () => state;

export const useChat = () =>
  useSyncExternalStore(
    (notify) => {
      listeners.add(notify);
      return () => listeners.delete(notify);
    },
    () => state,
  );

/**
 * The chat this page carries. A project's: the one being browsed, or, on the person's own
 * pages (profile, notifications), the one framing them. With none framing them, their own,
 * with the Assistant alone (`account`). `prop` is where the page holds it.
 */
export function useChatProject(): {
  project: SharedProps['project'];
  account: SharedProps['accountChat'];
  prop: 'project' | 'chatProject' | 'accountChat';
} {
  const { project, chatProject, accountChat } = usePage<SharedProps>().props;
  if (project) return { project, account: null, prop: 'project' };
  if (chatProject) return { project: chatProject, account: null, prop: 'chatProject' };
  return { project: null, account: accountChat ?? null, prop: 'accountChat' };
}

/**
 * The address of one of the chat's actions: the project's, or (in none) the person's own. The
 * same function while the project stays (what loads the chat depends on it)
 */
export function useChatRoute() {
  const projectId = useChatProject().project?.id;
  return useCallback(
    (name: string, params: Record<string, string | number> = {}) =>
      projectId
        ? route(`chat.${name}`, { project: projectId, ...params })
        : route(`account.chat.${name}`, params),
    [projectId],
  );
}
