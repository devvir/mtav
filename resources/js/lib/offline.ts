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

/** How often, while it seems out of reach, the server is asked whether it is back */
export const PROBE_EVERY_MS = 3000;
let probing: ReturnType<typeof setInterval> | undefined;

/**
 * Whether the server answers, asked directly (its health address, which the worker leaves
 * alone): one failed request is no proof, and nothing else may come to say it is back
 */
export async function probe(): Promise<void> {
  try {
    const response = await fetch('/up', { cache: 'no-store' });
    if (response.ok) setOnline(true);
  } catch {
    // Still out of reach: asked again shortly
  }
}

const setOnline = (online: boolean) => {
  if (connection.get().online !== online) connection.set({ online });
  clearInterval(probing);
  probing = online ? undefined : setInterval(() => void probe(), PROBE_EVERY_MS);
};

/** A change attempted while the server cannot be reached: said, and not sent (and asked again) */
export function heldOffline(): boolean {
  if (connection.get().online) return false;
  void probe();
  toast({
    tone: 'warning',
    title: _('You are offline'),
    message: _('Nothing was sent. Try again when the connection is back.'),
  });
  return true;
}

/** Whose pages the device keeps: another person's are forgotten */
const KEPT_FOR = 'mtav.kept-for';

/**
 * The device now shows `userId`'s pages (null: a guest's): another person's are forgotten.
 * Who they are kept for is noted only once the worker was told, so a page the worker does
 * not serve (a hard reload) leaves it to the next.
 */
export async function keepFor(userId: number | null) {
  const now = String(userId ?? '');
  let before: string | null = null;
  try {
    before = localStorage.getItem(KEPT_FOR);
  } catch {
    // Without storage, keep nothing across people: forget every time
  }
  if (before === now) return;

  const workers = navigator.serviceWorker;
  if (workers) {
    const registration = workers.controller
      ? undefined
      : await workers.getRegistration().catch(() => undefined);
    const worker = workers.controller ?? registration?.active;
    // Registered but not yet working: nothing to tell yet, nor kept by it
    if (!worker && registration) return;
    worker?.postMessage('forget');
  }
  try {
    localStorage.setItem(KEPT_FOR, now);
  } catch {
    // As above
  }
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
  void keepFor(userId);

  window.addEventListener('offline', () => setOnline(false));
  // Back from sleep, or to a tab left a while: what it last heard may be stale
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !connection.get().online) void probe();
  });
  window.addEventListener('online', () => setOnline(true));
  // Said once, in the bar: not thrown again for every visit, prefetch or part that fails
  router.on('networkError', (event) => {
    setOnline(false);
    event.preventDefault();
  });
  router.on('success', () => {
    if (navigator.onLine) setOnline(true);
  });
  router.on('navigate', (event) => void keepFor(event.detail.page.props.auth?.user?.id ?? null));

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
