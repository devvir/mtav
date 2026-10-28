import { route as ziggyRoute, type Config, type RouteParams } from 'ziggy-js';

/**
 * The route list (sent once, then remembered) with the current visit's defaults, which
 * include the project being browsed, so route('families.index') just works inside a
 * project. Kept current by the shell (useCurrentPage) as each visit renders.
 */
let config: Config | null = null;
/** The current route's name, as the server matched it (null on a page no route names) */
let current: string | null = null;

export const setRoutes = (routes: unknown, visit: unknown) => {
  const { name, ...rest } = visit as Pick<Config, 'defaults' | 'location'> & { name?: string };
  config = { ...(routes as Config), ...rest };
  current = name ?? null;
};

/** A named route's URL, as Laravel's route() */
export function route(
  name: string,
  params?: RouteParams<string> | unknown,
  absolute = false,
): string {
  return ziggyRoute(name, params as RouteParams<string>, absolute, config!);
}

const PATTERNS = new Map<string, RegExp>();

/**
 * Whether the current route has one of these names (wildcards allowed: 'manage.families.*').
 * Compares names, never URLs: the sidebar asks for every item on every render.
 */
export function routeIs(...patterns: string[]): boolean {
  if (!current) return false;

  return patterns.some((pattern) => {
    let regex = PATTERNS.get(pattern);
    if (!regex) {
      regex = new RegExp(`^${pattern.replaceAll('.', '\\.').replaceAll('*', '.*')}$`);
      PATTERNS.set(pattern, regex);
    }

    return regex.test(current!);
  });
}
