import { router } from '@inertiajs/react';
import { createStore, useStore } from './store';
import { toast } from './toasts';
import { _ } from './translations';

/**
 * Working without the server. The service worker (public/sw.js) keeps each page as it was
 * last loaded and answers with it when the server cannot be reached; this side knows when
 * that is (the worker says so, the browser loses its network, a visit fails), shows it,
 * and holds every change until the server is back: nothing is sent to be lost.
 */
export const connection = createStore<{ online: boolean }>({ online: true });

export const useOnline = () => useStore(connection).online;

const setOnline = (online: boolean) => {
  if (connection.get().online !== online) connection.set({ online });
};

/** A change attempted while the server cannot be reached: said, and not sent */
export function heldOffline(): boolean {
  if (connection.get().online) return false;
  toast({
    tone: 'warning',
    title: _('You are offline'),
    message: _('Changes are held until the connection is back: nothing was sent.'),
  });
  return true;
}

/** Whose pages the device keeps: another person's are forgotten */
const KEPT_FOR = 'mtav.kept-for';

function keepFor(userId: number | null) {
  let before: string | null = null;
  try {
    before = localStorage.getItem(KEPT_FOR);
    localStorage.setItem(KEPT_FOR, String(userId ?? ''));
  } catch {
    // Without storage, keep nothing across people: forget every time
  }
  if (before !== String(userId ?? '')) navigator.serviceWorker?.controller?.postMessage('forget');
}

/**
 * Starts working offline: the worker, the status, and the guard on changes. `userId` is who
 * is signed in (null for a guest).
 */
export function workOffline(userId: number | null) {
  if ('serviceWorker' in navigator) {
    void navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data?.type === 'connection') setOnline(Boolean(event.data.online));
    });
  }
  keepFor(userId);

  window.addEventListener('offline', () => setOnline(false));
  window.addEventListener('online', () => setOnline(true));
  // Said once, in the bar: not thrown again for every visit, prefetch or part that fails
  router.on('networkError', (event) => {
    setOnline(false);
    event.preventDefault();
  });
  router.on('success', () => {
    if (navigator.onLine) setOnline(true);
  });
  router.on('navigate', (event) => keepFor(event.detail.page.props.auth?.user?.id ?? null));

  // Offline, nothing that changes something leaves the page
  router.on('before', (event) => {
    if (event.detail.visit.method !== 'get' && heldOffline()) event.preventDefault();
  });
}

/**
 * The main places of a project, loaded once in the background after arriving: they are
 * there offline even before being visited
 */
export function keepPlaces(hrefs: string[]) {
  if (!('serviceWorker' in navigator)) return;
  hrefs.forEach((href) => router.prefetch(href, { method: 'get' }, { cacheFor: '30s' }));
}
