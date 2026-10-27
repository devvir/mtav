import { usePage } from '@inertiajs/vue3';
import { route as ziggyRoute, type Config, type RouteParams } from 'ziggy-js';

/**
 * Named routes, as Laravel's route(). Uses the route list of the current visit, whose
 * defaults include the project being browsed (so route('families.index') just works
 * inside a project).
 */
export function route(
  name: string,
  params?: RouteParams<string> | unknown,
  absolute = false,
): string {
  const config = usePage().props.ziggy as unknown as Config;

  return ziggyRoute(name, params as RouteParams<string>, absolute, config);
}

/**
 * Whether the current URL matches a route name (wildcards allowed: 'manage.families.*').
 */
export function routeIs(...patterns: string[]): boolean {
  const config = usePage().props.ziggy as unknown as Config;
  const current = ziggyRoute(undefined, undefined, undefined, config);

  return patterns.some((pattern) => current.current(pattern));
}
