import type { SharedProps } from '@/types/page';
import { usePage } from '@inertiajs/react';
import { useSyncExternalStore } from 'react';

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
 * The project whose chat this page carries: the one being browsed, or, on the person's own
 * pages (profile, notifications), the one framing them. `prop` is where the page holds it.
 */
export function useChatProject(): {
  project: SharedProps['project'];
  prop: 'project' | 'chatProject';
} {
  const { project, chatProject } = usePage<SharedProps>().props;
  return project
    ? { project, prop: 'project' }
    : { project: chatProject ?? null, prop: 'chatProject' };
}
