<script setup lang="ts">
import { CardFooter, CreatedMeta } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <CardFooter class="flex items-center justify-between gap-2" v-slot="{ cardType }">
    <div v-if="unit.family" class="flex min-w-0 flex-1 items-center gap-2">
      {{ _('Family') }}
      <ModalLink :href="route('families.show', unit.family.id)" class="min-w-0">
        <span
          class="block truncate text-text-link hover:text-text-link-hover"
          :title="unit.family.name"
        >
          {{ unit.family.name }}
        </span>
      </ModalLink>
    </div>

    <CreatedMeta v-if="cardType === 'show' || !unit.family" class="ml-auto" />
  </CardFooter>
</template>
