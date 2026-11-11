import { PageHeader } from '@/design';
import { Alert, Stack } from '@mantine/core';
import type { Job, Readings } from '../api';
import { BackupsPanel } from './deployment/BackupsPanel';
import { EnvironmentsPanel } from './deployment/EnvironmentsPanel';
import { Figures } from './deployment/Figures';
import { InfrastructurePanel } from './deployment/InfrastructurePanel';
import { JobsPanel } from './deployment/JobsPanel';
import { ServersPanel } from './deployment/ServersPanel';
import { useActions } from './deployment/useActions';

/** Deploying and running MTAV: its environments, AWS's servers, the infrastructure, money and backups, what it is doing */
export function Deployment({ readings, jobs }: { readings?: Readings; jobs: Job[] }) {
  const { start, running, shown, show, problem, dismiss } = useActions(jobs);
  const acting = { start, running };

  return (
    <>
      <PageHeader
        title="Deployment"
        description="Where each environment runs and what, AWS's servers, the infrastructure, the money and the backups."
      />
      {problem ? (
        <Alert color="red" variant="light" mb="md" withCloseButton onClose={dismiss}>
          {problem}
        </Alert>
      ) : null}

      <Figures readings={readings} />

      <Stack gap="lg">
        <EnvironmentsPanel readings={readings} {...acting} />

        <div className="warden-columns">
          <ServersPanel readings={readings} {...acting} />
          <Stack gap="lg">
            <InfrastructurePanel jobs={jobs} {...acting} />
            <BackupsPanel readings={readings} {...acting} />
          </Stack>
        </div>

        <JobsPanel jobs={jobs} shown={shown} onShow={show} />
      </Stack>
    </>
  );
}
