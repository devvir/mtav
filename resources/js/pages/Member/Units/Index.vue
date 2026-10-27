<script setup lang="ts">
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _, _n } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { Map } from 'lucide-vue-next';

defineProps<{ unitTypes: UnitType[] }>();

useProjectChanges({ units: ['unitTypes'], unit_types: ['unitTypes'], lottery_runs: ['unitTypes'] });
</script>

<template>
  <PageHeader
    :title="_('Units')"
    :description="
      _('The project\'s dwellings, by type. Families compete only for units of their own type.')
    "
  >
    <template #actions>
      <Link
        :href="route('plan.show')"
        class="inline-flex min-h-11 items-center gap-2 font-medium text-text-link hover:underline"
        ><Map class="size-5" /> {{ _('See them on the plan') }}</Link
      >
    </template>
  </PageHeader>

  <section v-for="type in unitTypes" :key="type.id" class="mb-8">
    <h2 class="text-lg font-semibold">{{ type.name }}</h2>
    <p class="mb-3 text-sm text-text-muted">
      {{ type.description ? type.description + ' · ' : ''
      }}{{
        [
          _n('{count} unit', '{count} units', type.units?.length ?? 0),
          _n('{count} family', '{count} families', type.families_count ?? 0),
        ].join(', ')
      }}
    </p>
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <li v-for="unit in type.units" :key="unit.id">
        <Link
          :href="route('units.show', unit.id)"
          class="block rounded-xl border-2 border-border bg-surface p-3 hover:border-border-interactive"
        >
          <span class="block font-semibold">{{ unit.identifier }}</span>
          <span
            class="block truncate text-sm"
            :class="unit.family ? 'text-success' : 'text-text-subtle'"
            >{{ unit.family?.name ?? _('Unassigned') }}</span
          >
        </Link>
      </li>
    </ul>
  </section>
  <p v-if="!unitTypes.length" class="py-8 text-center text-text-muted">{{ _('No units yet.') }}</p>
</template>
