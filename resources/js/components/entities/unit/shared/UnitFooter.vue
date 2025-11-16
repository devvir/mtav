<script setup lang="ts">
import { Badge } from '@/components/badge';
import { CardFooter, CreatedMeta } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <CardFooter class="flex justify-between items-center gap-2" v-slot="{ cardType }">
    <div v-if="unit.family" class="flex items-center gap-2 min-w-0 flex-1">
      <Badge variant="success" class="px-2 py-0.5 text-xs flex-shrink-0">
        {{ _('Assigned') }}
      </Badge>

      <ModalLink :href="route('families.show', unit.family.id)" class="min-w-0">
        <span
          class="text-text-link hover:text-text-link-hover truncate block"
          :title="unit.family.name"
        >
          {{ unit.family.name }}
        </span>
      </ModalLink>
    </div>

    <CreatedMeta v-if="cardType === 'show' || !unit.family" class="ml-auto" />
  </CardFooter>
</template>