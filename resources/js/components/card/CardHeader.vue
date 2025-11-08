<script setup lang="ts">
import { cn } from '@/lib/utils';
import EditButton from '@/components/EditButton.vue';
import * as exposed from './exposed';
import type { Component } from 'vue';

const props = defineProps<{
  title: string;
  subtitle?: string;
  icon?: Component | string;
  class?: string;
}>();

const resource = inject<ApiResource | undefined>(exposed.resource);
const editUrl = inject<string | undefined>(exposed.editUrl);
</script>

<template>
  <header
    :class="cn(
      'grid items-start gap-3 p-6',
      icon ? 'grid-cols-[auto_1fr_auto]' : 'grid-cols-[1fr_auto]',
      'min-w-0', // Critical for truncation in grid
      props.class
    )"
  >
    <!-- Icon Column -->
    <div v-if="icon" class="flex-shrink-0 pt-0.5">
      <component v-if="typeof icon !== 'string'" :is="icon" class="size-10 text-text-subtle" />
      <span v-else class="text-3xl leading-none">{{ icon }}</span>
    </div>

    <!-- Content Column -->
    <section class="min-w-0 space-y-2">
      <!-- Top slot for badges, etc -->
      <div v-if="$slots.default" class="flex items-center gap-2 flex-wrap">
        <slot />
      </div>

      <!-- Title (required) -->
      <h2 :class="cn(
        'text-lg font-semibold text-text leading-tight truncate min-w-0',
        subtitle ? 'text-xl' : 'text-2xl'
      )" :title="title">{{ title }}</h2>

      <!-- Subtitle (optional) -->
      <p v-if="subtitle"
        :class="cn('text-sm text-text-muted leading-tight truncate min-w-0')" :title="subtitle">{{ subtitle }}</p>
    </section>

    <!-- Edit Button Column -->
    <div v-if="editUrl && resource">
      <EditButton :resource :route-name="editUrl" />
    </div>
  </header>
</template>