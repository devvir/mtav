import { _ } from '@/composables/useTranslations';
import { capitalize } from '@/lib/utils';

type EntityLabelCount = number | 'singular' | 'plural';

const type2ns: Record<AppEntity, AppEntityNS> = {
  project: 'projects',
  unit: 'units',
  unit_type: 'unit_types',
  admin: 'admins',
  family: 'families',
  member: 'members',
  event: 'events',
  media: 'media',
  log: 'logs',
};

const type2plural = {
  ...type2ns,
  media: 'items',
} as Record<AppEntity, AppEntityPluralForm>;

const ns2type = Object.fromEntries(
  Object.entries(type2ns).map(([ns, name]) => [name, ns]) as [AppEntityNS, AppEntity][],
) as Record<AppEntityNS, AppEntity>;

/**
 * List of actions that all Resources implement.
 */
const actions: ResourceAction[] = ['index', 'show', 'create', 'edit', 'destroy', 'restore'];

/**
 * @returns The namespace for a given resource type (e.g. 'member' => 'members')
 */
const entityPlural = (resource: AppEntity) => type2plural[resource];

/**
 * @returns The namespace for a given resource type (e.g. 'member' => 'members')
 */
const entityNS = (resource: AppEntity) => type2ns[resource];

/**
 * @returns The resource type for a given namespace (e.g. 'members' => 'member')
 */
const entityFromNS = (namespace: AppEntityNS) => ns2type[namespace];

/**
 * @returns The translated, correctly pluralized label for a given entity
 *          (e.g., 'Member' or 'Members')
 */
const entityLabel = (entity: AppEntity, count: EntityLabelCount = 'singular'): string => {
  const singular = count === 1 || count === 'singular';

  return _(capitalize(singular ? entity : entityPlural(entity)));
};

/**
 * @returns The list of routes for a given entity.
 */
const entityRoutes = (entity: AppEntity) =>
  actions.reduce((list, action) => {
    return { ...list, [action]: `${entityNS(entity)}.${action}` };
  }, {}) as Record<ResourceAction, string>;

/**
 * Composable exports
 */
export default () => ({
  actions,
  entityFromNS,
  entityLabel,
  entityNS,
  entityPlural,
  entityRoutes,
});

export { actions, entityFromNS, entityLabel, entityNS, entityPlural, entityRoutes };
