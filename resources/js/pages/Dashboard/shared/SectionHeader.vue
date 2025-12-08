<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { ModalLink } from '@inertiaui/modal-vue';
import { Plus } from 'lucide-vue-next';

defineProps<{
  title: string;
  viewAllHref?: string;
  viewAllText?: string;
  align?: 'left' | 'right';
  /** Create action */
  createHref?: string;
  createIf?: MaybeRef<boolean>;
  createLabel?: string;
}>();
</script>

<template>
  <div class="mb-3 flex items-center justify-between gap-4">
    <Link
      v-if="viewAllHref"
      :href="route(viewAllHref)"
      :class="['flex-1', align === 'right' ? 'text-right' : '']"
    >
      <h2 class="text-lg font-semibold text-text hover:text-text-link focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none">
        {{ title }}
      </h2>
    </Link>
    <h2 v-else :class="['flex-1 text-lg font-semibold text-text', align === 'right' ? 'text-right' : '']">
      {{ title }}
    </h2>
    <div class="flex items-center gap-3">
      <Link
        v-if="viewAllText"
        :href="route(viewAllHref)"
        class="shrink-0 text-sm text-text-link hover:text-text-link-hover hover:underline focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"
      >
        {{ viewAllText || _('View all') }}
      </Link>

      <ModalLink
        v-if="createHref && toValue(createIf) !== false"
        :href="route(createHref)"
        slideover
        class="shrink-0 rounded-md p-1 text-text-subtle hover:text-text focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"
        :title="createLabel"
        :aria-label="createLabel"
      >
        <Plus class="h-5 w-5" />
      </ModalLink>
    </div>
  </div>
</template>
