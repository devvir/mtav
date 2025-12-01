<script setup lang="ts">
import { CardFooter, CreatedMeta } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { Home } from 'lucide-vue-next';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <CardFooter class="flex justify-between text-xs gap-base text-nowrap">
    <span v-if="family.unit?.id" class="flex items-center gap-1.5 text-green-700 dark:text-green-300 font-medium truncate">
      <Home class="h-3 w-3 shrink-0" />
      <ModalLink
        :href="route('units.show', family.unit.id)"
        class="hover:underline truncate"
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
