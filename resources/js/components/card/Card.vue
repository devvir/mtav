<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import { HTMLAttributes } from 'vue';

const props = defineProps<{
  dimmed?: boolean;
  cardLink?: string;
  noModal?: boolean;
  class?: HTMLAttributes['class'];
  panelClasses?: HTMLAttributes['class'];
}>();
</script>

<template>
  <component
    :is="cardLink ? (noModal ? Link : ModalLink) : 'article'"
    :href="cardLink"
    :prefetch="cardLink ? ($attrs.prefetch ?? 'hover') : undefined"
    :title="cardLink ? ($attrs.title ?? _('Click for more information')) : undefined"
    :panel-classes
    :class="
      cn(
        'relative w-full max-w-3xl rounded-lg border *:p-4',
        'border-border bg-surface-elevated shadow-sm [&>*+*]:border-border-subtle',
        // Dimmed styling for inactive/unpublished content
        props.dimmed && 'opacity-60',
        $props.class,
        'flex h-full flex-col [&>*+*]:border-t', // No override allowed
        cardLink &&
          'cursor-pointer transition-all hover:shadow-danger focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none',
      )
    "
  >
    <slot />
  </component>
</template>
