<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

/**
 * The submit (and optional cancel) of a form page.
 */
defineProps<{
  submitLabel: string;
  processing?: boolean;
  cancelHref?: string;
}>();
</script>

<template>
  <div
    class="flex flex-col-reverse gap-3 border-t border-border-subtle pt-5 sm:flex-row sm:items-center sm:justify-end"
  >
    <slot name="secondary" />
    <Button v-if="cancelHref" variant="outline" as-child>
      <Link :href="cancelHref">{{ _('Cancel') }}</Link>
    </Button>
    <Button type="submit" :disabled="processing">
      <LoaderCircle v-if="processing" class="animate-spin" />
      {{ submitLabel }}
    </Button>
  </div>
</template>
