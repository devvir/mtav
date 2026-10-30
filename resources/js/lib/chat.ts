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
