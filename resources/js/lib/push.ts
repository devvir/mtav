import { router } from '@inertiajs/react';
import { route } from './route';

/**
 * Notifications on this device, even with the app closed (Web Push): the browser asks the
 * person once, then its push service delivers what the server sends. The service worker
 * (public/sw.js) shows them.
 */
export type PushState = 'unsupported' | 'needs-install' | 'blocked' | 'off' | 'on';

const supported = () =>
  'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

/** iPhones and iPads take notifications only from an app added to the home screen */
const appleNotInstalled = () =>
  /iPhone|iPad|iPod/.test(navigator.userAgent) &&
  !(navigator as Navigator & { standalone?: boolean }).standalone;

const subscription = async () =>
  (await navigator.serviceWorker.ready).pushManager.getSubscription();

export async function pushState(): Promise<PushState> {
  if (!supported()) return appleNotInstalled() ? 'needs-install' : 'unsupported';
  if (Notification.permission === 'denied') return 'blocked';
  return (await subscription()) ? 'on' : 'off';
}

/** The server's public key, as browsers take it */
const keyBytes = (key: string) => {
  const base64 = (key + '='.repeat((4 - (key.length % 4)) % 4))
    .replace(/-/g, '+')
    .replace(/_/g, '/');
  return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
};

const tell = (
  method: 'post' | 'delete',
  data: { endpoint?: string; keys?: Record<string, string> },
) =>
  new Promise<void>((done) =>
    router.visit(route(method === 'post' ? 'push.subscribe' : 'push.unsubscribe'), {
      method,
      data: data as Record<string, string | Record<string, string>>,
      preserveScroll: true,
      preserveState: true,
      onFinish: () => done(),
    }),
  );

/** Asks the person (the browser's own question), then subscribes this device */
export async function turnOn(key: string): Promise<PushState> {
  if ((await Notification.requestPermission()) !== 'granted') return pushState();
  const registration = await navigator.serviceWorker.ready;
  const subscribed =
    (await registration.pushManager.getSubscription()) ??
    (await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: keyBytes(key),
    }));
  const { endpoint, keys } = subscribed.toJSON();
  await tell('post', { endpoint, keys });
  return 'on';
}

export async function turnOff(): Promise<PushState> {
  const subscribed = await subscription();
  if (subscribed) {
    await tell('delete', { endpoint: subscribed.endpoint });
    await subscribed.unsubscribe();
  }
  return 'off';
}
