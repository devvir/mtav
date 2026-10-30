import { pushState, turnOff, turnOn, type PushState } from '@/lib/push';
import { _ } from '@/lib/translations';
import { Switch, Text } from '@mantine/core';
import { useEffect, useState } from 'react';

/**
 * Whether this device shows the project's notifications even with the app closed: a
 * switch, asked for by the person (browsers block sites that ask on arrival). Where it
 * cannot be, it says why.
 */
export function DeviceNotifications({ pushKey }: { pushKey: string }) {
  const [state, setState] = useState<PushState | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    void pushState().then(setState);
  }, []);

  if (!state) return null;

  const change = async (on: boolean) => {
    setBusy(true);
    setState(await (on ? turnOn(pushKey) : turnOff()).catch(() => pushState()));
    setBusy(false);
  };

  const why = {
    unsupported: _('This browser cannot show notifications.'),
    'needs-install': _(
      'On iPhone and iPad, add the app to the home screen first, then turn them on from there.',
    ),
    blocked: _('Notifications are blocked for this site: allow them in the browser settings.'),
  } as Partial<Record<PushState, string>>;

  return (
    <div data-push={state}>
      <Switch
        label={_('Notifications on this device')}
        description={_('What happens in your projects, even with the app closed.')}
        checked={state === 'on'}
        disabled={busy || Boolean(why[state])}
        onChange={(event) => void change(event.currentTarget.checked)}
      />
      {why[state] ? (
        <Text className="ds-caption" mt="xs">
          {why[state]}
        </Text>
      ) : null}
    </div>
  );
}
