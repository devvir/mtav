<script setup lang="ts">
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _, _n } from '@/composables/useTranslations';
import type { Family, Paginated } from '@/types/models';
import { Link, usePage } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';

defineProps<{ families: Paginated<Family>; filters: { q: string | null } }>();

const own = computed(() => usePage().props.project?.family?.id);

useProjectChanges({ families: ['families'], people: ['families'], lottery_runs: ['families'] });
</script>

<template>
  <PageHeader
    :title="_('Families')"
    :description="_('Each family ranks the units of its type and receives one in the lottery.')"
  >
    <template #info>
      <InfoTip :title="_('What is a family?')">
        <p>
          {{
            _(
              'The family takes part in the lottery as one: its members share one ranking, and it receives one unit.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>

  <SearchField
    :model-value="filters.q"
    :placeholder="_('Search families or people')"
    class="mb-4 max-w-md"
  />

  <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <li v-for="family in families.data" :key="family.id">
      <Link
        :href="route('families.show', family.id)"
        :class="[
          'block h-full rounded-2xl border-2 bg-surface p-4 hover:border-border-interactive',
          family.id === own ? 'border-success/60' : 'border-border',
        ]"
      >
        <p class="font-semibold">
          {{ family.name }}
          <span v-if="family.id === own" class="text-sm font-normal text-success"
            >· {{ _('your family') }}</span
          >
        </p>
        <p class="text-sm text-text-muted">
          {{ family.unit_type?.name }} ·
          {{ _n('{count} person', '{count} people', family.members_count ?? 0) }}
        </p>
        <p v-if="family.members?.length" class="mt-2 line-clamp-2 text-sm">
          {{ family.members.map((m) => m.firstname).join(', ') }}
        </p>
        <p v-if="family.unit" class="mt-2 flex items-center gap-1 text-sm font-medium text-success">
          <Home class="size-4" /> {{ family.unit.identifier }}
        </p>
      </Link>
    </li>
  </ul>
  <p v-if="!families.data.length" class="py-8 text-center text-text-muted">
    {{ _('No families yet.') }}
  </p>

  <Pagination :page="families" />
</template>
