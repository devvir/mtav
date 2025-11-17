<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ModalLink } from '@inertiaui/modal-vue';

const props = defineProps<{
  dimmed?: boolean;
  cardLink?: string;
}>();
</script>

<template>
  <component
    :is="cardLink ? ModalLink : 'article'"
    :href="cardLink"
    :prefetch="cardLink ? ($attrs.prefetch ?? 'hover') : undefined"
    :title="cardLink ? ($attrs.title ?? _('Click for more information')) : undefined"
    :class="
      cn(
        'w-full max-w-3xl rounded-lg border [&>*]:p-4 relative',
        'border-border bg-surface-elevated shadow-sm [&>*+*]:border-border-subtle',
        // Dimmed styling for inactive/unpublished content
        props.dimmed && 'opacity-60',
        $attrs.class,
        'flex h-full flex-col [&>*+*]:border-t', // No override allowed
        cardLink &&
          'cursor-pointer transition-all hover:shadow-danger focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none',
      )
    "
  >
    <slot />
  </component>
</template>
