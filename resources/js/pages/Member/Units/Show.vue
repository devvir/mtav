<script setup lang="ts">
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Unit } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{ unit: Unit }>();
</script>

<template>
  <PageHeader
    :title="unit.identifier"
    :description="unit.type?.name"
    :back="{ href: route('units.index'), label: _('Units') }"
  />
  <div class="max-w-xl rounded-2xl border-2 border-border bg-surface p-5">
    <p v-if="unit.family">
      {{ _('Assigned to') }}
      <Link
        :href="route('families.show', unit.family.id)"
        class="font-semibold text-text-link hover:underline"
        >{{ unit.family.name }}</Link
      >
    </p>
    <p v-else class="text-text-muted">
      {{ _('Not assigned yet: the lottery decides which family receives it.') }}
    </p>
  </div>
</template>
