import { router } from '@inertiajs/react';
import { heldOffline } from './offline';
import { route } from './route';
import { toast } from './toasts';
import { _ } from './translations';

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
  supported()
    ? ((await (await navigator.serviceWorker.getRegistration())?.pushManager.getSubscription()) ??
      null)
    : null;

/** On only if the browser has it and the server sends to it: `devices`, this person's */
export async function pushState(devices: string[]): Promise<PushState> {
  if (!supported()) return appleNotInstalled() ? 'needs-install' : 'unsupported';
  if (Notification.permission === 'denied') return 'blocked';
  const subscribed = await subscription();
  return subscribed && devices.includes(subscribed.endpoint) ? 'on' : 'off';
}

/** The server's public key, as browsers take it */
const keyBytes = (key: string) => {
  const base64 = (key + '='.repeat((4 - (key.length % 4)) % 4))
    .replace(/-/g, '+')
    .replace(/_/g, '/');
  return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
};

/** Whether the server took it */
const tell = (
  method: 'post' | 'delete',
  data: { endpoint?: string; keys?: Record<string, string> },
) =>
  new Promise<boolean>((done) => {
    let took = false;
    let refusal: string | undefined;
    router.visit(route(method === 'post' ? 'push.subscribe' : 'push.unsubscribe'), {
      method,
      data: data as Record<string, string | Record<string, string>>,
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        took = true;
      },
      onError: (errors) => {
        refusal = Object.values(errors)[0];
      },
      // Refused, or never there (the connection, the server failing)
      onFinish: () => {
        if (!took) {
          toast({ tone: 'error', message: refusal ?? _('That did not work. Please try again.') });
        }
        done(took);
      },
    });
  });

/** Asks the person (the browser's own question), then subscribes this device */
export async function turnOn(key: string): Promise<PushState> {
  if (heldOffline()) return 'off';
  const permission = await Notification.requestPermission();
  if (permission !== 'granted') return permission === 'denied' ? 'blocked' : 'off';
  const registration = await navigator.serviceWorker.ready;
  const subscribed =
    (await registration.pushManager.getSubscription()) ??
    (await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: keyBytes(key),
    }));
  const { endpoint, keys } = subscribed.toJSON();
  if (await tell('post', { endpoint, keys })) return 'on';
  await subscribed.unsubscribe();
  return 'off';
}

export async function turnOff(): Promise<PushState> {
  if (heldOffline()) return 'on';
  const subscribed = await subscription();
  if (subscribed) {
    await tell('delete', { endpoint: subscribed.endpoint });
    await subscribed.unsubscribe();
  }
  return 'off';
}

/** This browser stops taking notifications; the server forgets it on its next try, if not told */
export async function dropDevice() {
  const subscribed = await subscription().catch(() => null);
  await subscribed?.unsubscribe().catch(() => undefined);
  return subscribed?.endpoint ?? null;
}

/** Whoever signs out here takes their notifications with them: the server is told which device */
export async function signOut() {
  if (heldOffline()) return;
  router.post(route('logout'), { endpoint: await dropDevice() });
}
