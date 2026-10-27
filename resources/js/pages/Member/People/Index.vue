<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Paginated, Person } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{ people: Paginated<Person>; filters: { q: string | null } }>();

useProjectChanges({ people: ['people'] });
</script>

<template>
  <PageHeader :title="_('People')" :description="_('Everyone taking part in the project.')" />

  <SearchField :model-value="filters.q" :placeholder="_('Search by name')" class="mb-4 max-w-md" />

  <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <li v-for="person in people.data" :key="person.id">
      <Link
        :href="route('people.show', person.id)"
        class="flex items-center gap-3 rounded-2xl border-2 border-border bg-surface p-3 hover:border-border-interactive"
      >
        <PersonAvatar :name="person.name" :src="person.avatar" />
        <span class="min-w-0 flex-1">
          <span class="block truncate font-semibold">{{ person.name }}</span>
          <span class="block truncate text-sm text-text-muted">{{
            person.family?.name ?? _('No family')
          }}</span>
        </span>
        <Badge v-if="person.is_admin" tone="info">{{ _('Admin') }}</Badge>
      </Link>
    </li>
  </ul>
  <p v-if="!people.data.length" class="py-8 text-center text-text-muted">
    {{ _('Nobody matches.') }}
  </p>

  <Pagination :page="people" />
</template>
