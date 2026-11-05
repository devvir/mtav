import { Logo, Status } from '@/design';
import { Group, SegmentedControl, Text } from '@mantine/core';
import { IconActivityHeartbeat, IconCloudUpload, IconFileText } from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import { useWarden } from './api';
import { Deployment } from './views/Deployment';
import { Logs } from './views/Logs';
import { Overview } from './views/Overview';

const TABS = [
  { value: 'overview', label: 'Overview', icon: IconActivityHeartbeat },
  { value: 'deployment', label: 'Deployment', icon: IconCloudUpload },
  { value: 'logs', label: 'Logs', icon: IconFileText },
] as const;
type Tab = (typeof TABS)[number]['value'];

const tabOf = (hash: string): Tab =>
  TABS.some((tab) => tab.value === hash.slice(1)) ? (hash.slice(1) as Tab) : 'overview';

/**
 * MTAV from above, on a developer's machine: everything at a glance, deploying and running it, and its logs. Which
 * tab is open is in the address, so a reload stays where it was.
 */
export function App() {
  const warden = useWarden();
  const [tab, setTab] = useState<Tab>(() => tabOf(window.location.hash));

  useEffect(() => {
    const follow = () => setTab(tabOf(window.location.hash));
    window.addEventListener('hashchange', follow);
    return () => window.removeEventListener('hashchange', follow);
  }, []);

  const choose = (value: string) => {
    window.location.hash = value;
    setTab(value as Tab);
  };

  return (
    <div className="warden">
      <header className="warden-bar ds-bar">
        <Logo name="MTAV" institution="Warden" size={34} />
        <SegmentedControl
          value={tab}
          onChange={choose}
          radius="xl"
          data={TABS.map((one) => ({
            value: one.value,
            label: (
              <Group gap={6} wrap="nowrap" justify="center">
                <one.icon size={16} />
                <span>{one.label}</span>
              </Group>
            ),
          }))}
        />
        <div className="warden-bar-end">
          {warden.error ? (
            <Status tone="negative">The warden does not answer</Status>
          ) : (
            <Text size="xs" c="dimmed">
              {warden.readings ? 'Live' : 'Reading…'}
            </Text>
          )}
        </div>
      </header>
      <main className="warden-page">
        {tab === 'overview' ? <Overview readings={warden.readings} /> : null}
        {tab === 'deployment' ? (
          <Deployment readings={warden.readings} jobs={warden.jobs ?? []} />
        ) : null}
        {tab === 'logs' ? <Logs readings={warden.readings} /> : null}
      </main>
    </div>
  );
}
