import { router } from '@inertiajs/react';
import { dropDevice } from './push';

/** Whose notifications this device takes: someone else signing in ends them */
const PUSH_FOR = 'mtav.push-for';

async function keepPushFor(userId: number | null) {
  if (!userId) return;
  try {
    const before = localStorage.getItem(PUSH_FOR);
    if (before && before !== String(userId)) await dropDevice();
    localStorage.setItem(PUSH_FOR, String(userId));
  } catch {
    // Without storage, nothing tells who was here before
  }
}

/**
 * The tab holds things of whoever is signed in (the chat, its live channels, whatever else
 * memory keeps). When they leave or someone else arrives, it starts over with a full load, so
 * that the next person sees nothing of the last one; a message on its way is shown first, and
 * the next visit does it. `userId` is who is signed in (null for a guest).
 */
export function followPerson(userId: number | null) {
  let person = userId;
  void keepPushFor(userId);

  router.on('navigate', ({ detail: { page } }) => {
    const now = page.props.auth?.user?.id ?? null;
    if (now === person) return;
    void keepPushFor(now);
    if (person === null) {
      person = now;
      return;
    }
    // What the browser's Back would bring back of them cannot be read anymore
    router.clearHistory();
    if (!Object.keys(page.flash).length) window.location.replace(page.url);
  });
}
