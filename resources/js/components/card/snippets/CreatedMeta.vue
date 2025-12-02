<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { fromUTC } from '@/composables/useDates';
import * as exposed from '../exposed';

const resource = inject(exposed.resource, {});
</script>

<template>
  <span class="text-xs" :title="fromUTC(resource.created_at)">
    {{ `${_('Created')}: ${resource.created_ago}` }}
  </span>
</template>
