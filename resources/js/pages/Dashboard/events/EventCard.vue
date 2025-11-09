<script setup lang="ts">
import { Calendar, Clock, MapPin, Globe, Users } from 'lucide-vue-next';
import { ModalLink } from '@inertiaui/modal-vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  event: Event;
}>();

const getTypeIcon = (event: Event) => {
  if (event.is_lottery) return Users;
  if (event.is_online) return Globe;
  if (event.is_onsite) return MapPin;
  return Calendar;
};

const getTypeBadgeClass = (type: string) => {
  switch (type) {
    case 'lottery': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'online': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    case 'onsite': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
};

const getTypeLabel = (type: string) => {
  switch (type) {
    case 'lottery': return _('Lottery');
    case 'online': return _('Online');
    case 'onsite': return _('On-site');
    default: return type;
  }
};
</script>

<template>
  <ModalLink
    :href="route('events.show', event.id)"
    class="block rounded-lg border border-border bg-surface-elevated p-4 transition-all hover:bg-surface-interactive-hover hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-focus-ring"
  >
    <div class="flex items-start gap-3">
      <div class="rounded-lg bg-surface-sunken p-2 mt-1">
        <component :is="getTypeIcon(event)" class="h-4 w-4 text-text-subtle" />
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1">
            <h3 class="font-medium text-text truncate">{{ event.title }}</h3>
            <p class="text-sm text-text-muted line-clamp-1 mt-1">{{ event.description }}</p>
          </div>

          <span
            :class="getTypeBadgeClass(event.type)"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap"
          >
            {{ getTypeLabel(event.type) }}
          </span>
        </div>

        <div class="flex items-center gap-4 mt-2 text-xs text-text-subtle">
          <div class="flex items-center gap-1">
            <Clock class="h-3 w-3" />
            <span>{{ event.start_date_formatted || _('No date set yet') }}</span>
          </div>
          <div v-if="event.location" class="flex items-center gap-1 truncate">
            <component :is="event.is_online ? Globe : MapPin" class="h-3 w-3 flex-shrink-0" />
            <span class="truncate">{{ event.location }}</span>
          </div>
        </div>
      </div>
    </div>
  </ModalLink>
</template>