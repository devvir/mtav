import { useSyncExternalStore } from 'react';

/**
 * A value kept outside React that components follow: what code anywhere asks for (a
 * dialog, a tour) and one host component draws.
 */
export function createStore<T>(initial: T) {
  let value = initial;
  const listeners = new Set<() => void>();

  return {
    get: () => value,
    set(next: T | ((current: T) => T)) {
      value = typeof next === 'function' ? (next as (current: T) => T)(value) : next;
      listeners.forEach((listener) => listener());
    },
    subscribe(listener: () => void) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
  };
}

export type Store<T> = ReturnType<typeof createStore<T>>;

/** The store's value, redrawing when it changes */
export const useStore = <T>(store: Store<T>): T =>
  useSyncExternalStore(store.subscribe, store.get, store.get);
