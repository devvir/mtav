import { _ } from '@/composables/useTranslations';

export interface EventBadgeConfig {
  text: string;
  priority: number;
  type: 'event-type' | 'status' | 'rsvp' | 'draft';
  variant:
   | 'lottery' | 'online' | 'onsite'
   | 'completed' | 'ongoing' | 'upcoming' | 'no-date'
   | 'draft' | 'rsvp';
  show?: boolean;
  adminOnly?: boolean;
}

/**
 * Composable for event badge logic and styling
 */
export const useEventBadges = (event: ComputedRef<Event>) => {
  /**
   * Event type badge configuration
   */
  const typeBadge = computed((): EventBadgeConfig => {
    const variantMap: Record<string, EventBadgeConfig['variant']> = {
      lottery: 'lottery',
      online: 'online',
      onsite: 'onsite',
    };

    const priorityMap: Record<string, number> = {
      lottery: 1,
      online: 2,
      onsite: 3,
    };

    return {
      text: event.type_label,
      priority: priorityMap[event.type] || 3,
      type: 'event-type',
      variant: variantMap[event.type] || 'onsite',
    };
  });

  /**
   * Status badge
   */
  const statusBadge = computed((): EventBadgeConfig => {
    const statusMap: Record<string, { variant: EventBadgeConfig['variant']; text: string }> = {
      completed: { variant: 'completed', text: _('Past') },
      ongoing: { variant: 'ongoing', text: _('Ongoing') },
      upcoming: { variant: 'upcoming', text: _('Upcoming') },
    };

    if (!event.start_date && !event.end_date) {
      return {
        text: _('No Date Set'),
        priority: 6,
        type: 'status',
        variant: 'no-date',
      };
    }

    const statusInfo = statusMap[event.status] || statusMap.upcoming;

    return {
      text: statusInfo.text,
      priority: 6,
      type: 'status',
      variant: statusInfo.variant,
    };
  });

  /**
   * RSVP configuration badge
   */
  const rsvpBadge = computed((): EventBadgeConfig => ({
    text: _('RSVP Required'),
    priority: 7, // After status badges
    type: 'rsvp',
    variant: 'rsvp',
    show: event.allows_rsvp,
  }));

  /**
   * Draft status badge (only shown when event is not published)
   */
  const draftBadge = computed((): EventBadgeConfig => ({
    text: _('Draft'),
    priority: 10, // Move to end of list
    type: 'draft',
    variant: 'draft',
    show: !event.is_published && event.status === 'upcoming',
    adminOnly: true,
  }));

  /**
   * Get all badges in priority order
   */
  const badges = computed(() => {
    const badgeList = [
      typeBadge.value,
      draftBadge.value,
      rsvpBadge.value,
      statusBadge.value,
    ].filter(badge => badge.show || typeof badge.show === 'undefined');

    return badgeList.sort((a, b) => a.priority - b.priority);
  });

  return {
    typeBadge,
    draftBadge,
    rsvpBadge,
    statusBadge,
    badges,
  };
};