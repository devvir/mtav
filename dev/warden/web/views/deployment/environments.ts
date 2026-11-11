import type { CloudServer, Readings } from '../../api';

export const ENVIRONMENTS = ['production', 'sorteo', 'staging', 'dev'] as const;
export type Environment = (typeof ENVIRONMENTS)[number];

/** The branch each environment follows */
export const BRANCH: Record<Environment, string> = {
  production: 'main',
  sorteo: 'main',
  staging: 'staging',
  dev: 'dev',
};

/** The environments some AWS server runs */
export const onAws = (cloud: CloudServer[]) =>
  ENVIRONMENTS.filter((environment) =>
    cloud.some((server) => server.environments.includes(environment)),
  );

/** Where an environment runs and what: on an AWS server, or nowhere (its names show the static page) */
export function whereIs(environment: Environment, readings?: Readings) {
  const server = readings?.cloud.data?.find((one) => one.environments.includes(environment));
  if (server) {
    return {
      where: `AWS · ${server.name}`,
      live: server.live?.[environment] ?? null,
      previous: server.previous?.[environment] ?? null,
    };
  }
  return { where: 'Off: the static page', live: null, previous: null };
}
