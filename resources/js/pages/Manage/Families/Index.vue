<script setup lang="ts">
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _, _n } from '@/composables/useTranslations';
import type { Family, Paginated, Ref } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, UserPlus, UsersRound } from 'lucide-vue-next';

defineProps<{
  families: Paginated<Family>;
  unitTypes: Ref[];
  filters: { q: string | null; unit_type: number | null };
}>();

async function remove(family: Family) {
  const confirmed = await confirm({
    title: _('Remove the {name} family?', { name: family.name }),
    message: _('Its members leave the project (admins stay admins). This cannot be undone.'),
    confirmLabel: _('Remove'),
    danger: true,
  });
  if (confirmed)
    router.delete(route('manage.families.destroy', family.id), { preserveScroll: true });
}

useProjectChanges({ families: ['families'], people: ['families'] });
</script>

<template>
  <PageHeader :title="_('Families')">
    <template #info>
      <InfoTip :title="_('What is a family?')">
        <p>
          {{
            _(
              'The lottery participant: its members share one ranking, and it receives one unit of its type.',
            )
          }}
        </p>
      </InfoTip>
    </template>
    <template #actions>
      <Button as-child
        ><Link :href="route('manage.families.create')"
          ><Plus /> {{ _('Add a family') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <SearchField
    :model-value="filters.q"
    :placeholder="_('Search families or people')"
    class="mb-4 max-w-md"
  />

  <ul
    v-if="families.data.length"
    class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface"
  >
    <li
      v-for="family in families.data"
      :key="family.id"
      class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center"
    >
      <div class="min-w-0 flex-1">
        <p class="font-semibold">{{ family.name }}</p>
        <p class="text-sm text-text-muted">
          {{ family.unit_type?.name }} ·
          {{ _n('{count} person', '{count} people', family.members_count ?? 0) }}
          <template v-if="family.unit">
            · <span class="text-success">{{ family.unit.identifier }}</span></template
          >
        </p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" as-child
          ><Link :href="route('manage.invitations.index', { family: family.id })"
            ><UserPlus /> {{ _('Invite') }}</Link
          ></Button
        >
        <Button
          variant="ghost"
          size="icon"
          as-child
          :aria-label="_('Edit {name}', { name: family.name })"
          ><Link :href="route('manage.families.edit', family.id)"><Pencil /></Link
        ></Button>
        <Button
          variant="ghost"
          size="icon"
          :disabled="Boolean(family.unit)"
          :aria-label="_('Remove {name}', { name: family.name })"
          @click="remove(family)"
          ><Trash2
        /></Button>
      </div>
    </li>
  </ul>
  <EmptyState v-else :icon="UsersRound" :title="_('No families yet')">
    <Button as-child
      ><Link :href="route('manage.families.create')">{{ _('Add the first one') }}</Link></Button
    >
  </EmptyState>

  <Pagination :page="families" />
</template>
