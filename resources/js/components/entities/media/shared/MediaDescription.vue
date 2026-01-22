<script setup lang="ts">
import { fromUTC } from '@/composables/useDates';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div
    class="absolute right-0 bottom-0 left-0 bg-linear-to-t from-black/85 via-black/60 via-60% to-transparent p-4 text-white"
  >
    <div class="space-y-2">
      <p class="text-lg leading-relaxed">
        {{ media.description || _('No description available') }}
      </p>
      <div class="flex items-center justify-between text-sm opacity-90">
        <span>{{ _('Published by') }} {{ media.owner?.name || _('Unknown user') }}</span>
        <span :title="fromUTC(media.created_at)">{{ media.created_ago }}</span>
      </div>
    </div>
  </div>
</template>
