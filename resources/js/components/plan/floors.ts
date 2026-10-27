import { _ } from '@/composables/useTranslations';

/** How a floor is called: ground floor at 0, basements below, floors above */
export const floorLabel = (floor: number) =>
  floor === 0
    ? _('Ground floor')
    : floor < 0
      ? _('Basement {n}', { n: -floor })
      : _('Floor {n}', { n: floor });
