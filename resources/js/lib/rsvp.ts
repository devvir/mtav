import type { ProjectEvent } from '@/types/models';
import { router } from '@inertiajs/react';
import { startTransition, useOptimistic } from 'react';
import { heldOffline } from './offline';
import { route } from './route';

/**
 * Someone's answer to whether they go to an event: shown at once, and back as it was should
 * the server refuse it (or the visit be cut off or cancelled); offline, nothing is sent. What
 * the event shows (on its list and in its details) is all that is asked again.
 */
export function useRsvp(event: Pick<ProjectEvent, 'id' | 'attending'>) {
  const [attending, show] = useOptimistic(event.attending);

  const answer = (going: boolean) => {
    if (heldOffline()) return;
    startTransition(async () => {
      show(going);
      await new Promise<void>((finished) =>
        router.put(
          route('events.rsvp', event.id),
          { attending: going },
          { preserveScroll: true, only: ['events', 'event'], onFinish: () => finished() },
        ),
      );
    });
  };

  return { attending, answer };
}
