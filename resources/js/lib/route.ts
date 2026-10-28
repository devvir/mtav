import { route as ziggyRoute, type Config, type RouteParams } from 'ziggy-js';

/**
 * The route list (sent once, then remembered) with the current visit's defaults, which
 * include the project being browsed, so route('families.index') just works inside a
 * project. Kept current by the shell (useCurrentPage) as each visit renders.
 */
let config: Config | null = null;

export const setRoutes = (routes: unknown, visit: unknown) => {
  config = { ...(routes as Config), ...(visit as Pick<Config, 'defaults' | 'location'>) };
};

/** A named route's URL, as Laravel's route() */
export function route(
  name: string,
  params?: RouteParams<string> | unknown,
  absolute = false,
): string {
  return ziggyRoute(name, params as RouteParams<string>, absolute, config!);
}

/** Whether the current URL matches a route name (wildcards allowed: 'manage.families.*') */
export function routeIs(...patterns: string[]): boolean {
  const current = ziggyRoute(undefined, undefined, undefined, config!);

  return patterns.some((pattern) => current.current(pattern));
}
