<script setup lang="ts">
import { CardFooter, CreatedMeta } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Home } from 'lucide-vue-next';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <CardFooter class="flex justify-between gap-base text-xs text-nowrap">
    <span
      v-if="family.unit?.id"
      class="flex items-center gap-1.5 truncate font-medium text-green-700 dark:text-green-300"
    >
      <Home class="h-3 w-3 shrink-0" />
      <ModalLink
        :href="route('units.show', family.unit.id)"
        class="truncate hover:underline"
        prefetch="click"
      >
        {{ family.unit.identifier || `Unit #${family.unit.id}` }}
      </ModalLink>
    </span>

    <span v-else class="truncate">
      {{ `${_('Unit Type')}: ${family.unit_type?.description || _('No Unit Type')}` }}
    </span>

    <CreatedMeta />
  </CardFooter>
</template>
