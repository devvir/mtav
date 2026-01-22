<script setup lang="ts">
import { fromUTC } from '@/composables/useDates';
import { _ } from '@/composables/useTranslations';
import * as exposed from '../exposed';

const props = defineProps<{
  creator?: User;
}>();

const resource = inject(exposed.resource, {});

const createdLabel = computed(() => {
  return props.creator
    ? _('Created {ago} by {creator}', { ago: resource.created_ago, creator: props.creator.name })
    : _('Created {ago}', { ago: resource.created_ago });
});
</script>

<template>
  <span class="text-xs" :title="fromUTC(resource.created_at)">
    {{ createdLabel }}
  </span>
</template>
