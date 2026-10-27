<script setup lang="ts">
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Paginated, Ref, Unit } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { LayoutGrid, Pencil, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
  units: Paginated<Unit>;
  unitTypes: Ref[];
  filters: { q: string | null; unit_type: number | null };
}>();

const type = ref(props.filters.unit_type);
watch(type, (unitType) =>
  router.get(
    route('manage.units.index'),
    {
      ...(unitType ? { unit_type: unitType } : {}),
      ...(props.filters.q ? { q: props.filters.q } : {}),
    },
    { preserveState: true },
  ),
);

async function remove(unit: Unit) {
  if (
    await confirm({
      title: _('Remove unit {name}?', { name: unit.identifier }),
      confirmLabel: _('Remove'),
      danger: true,
    })
  ) {
    router.delete(route('manage.units.destroy', unit.id), { preserveScroll: true });
  }
}

useProjectChanges({ units: ['units'], lottery_runs: ['units'] });
</script>

<template>
  <PageHeader :title="_('Units')">
    <template #actions>
      <Button as-child
        ><Link
          :href="
            route('manage.units.create', filters.unit_type ? { unit_type: filters.unit_type } : {})
          "
          ><Plus /> {{ _('Add units') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_16rem]">
    <SearchField :model-value="filters.q" />
    <SelectInput
      v-model="type"
      :aria-label="_('Unit type')"
      :options="[
        { value: '', label: _('All types') },
        ...unitTypes.map((t) => ({ value: t.id, label: t.name })),
      ]"
    />
  </div>

  <div
    v-if="units.data.length"
    class="overflow-x-auto rounded-2xl border-2 border-border bg-surface"
  >
    <table class="w-full text-left">
      <thead class="border-b-2 border-border text-sm text-text-muted">
        <tr>
          <th class="px-4 py-3 font-medium">{{ _('Identifier') }}</th>
          <th class="px-4 py-3 font-medium">{{ _('Type') }}</th>
          <th class="px-4 py-3 font-medium">{{ _('Assigned to') }}</th>
          <th class="px-4 py-3">
            <span class="sr-only">{{ _('Actions') }}</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-border-subtle">
        <tr v-for="unit in units.data" :key="unit.id">
          <td class="px-4 py-2 font-semibold">{{ unit.identifier }}</td>
          <td class="px-4 py-2">{{ unit.type?.name }}</td>
          <td class="px-4 py-2" :class="unit.family ? 'text-success' : 'text-text-subtle'">
            {{ unit.family?.name ?? '—' }}
          </td>
          <td class="px-4 py-2 text-right whitespace-nowrap">
            <Button
              variant="ghost"
              size="icon"
              as-child
              :aria-label="_('Edit {name}', { name: unit.identifier })"
              ><Link :href="route('manage.units.edit', unit.id)"><Pencil /></Link
            ></Button>
            <Button
              variant="ghost"
              size="icon"
              :disabled="Boolean(unit.family)"
              :aria-label="_('Remove {name}', { name: unit.identifier })"
              @click="remove(unit)"
              ><Trash2
            /></Button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <EmptyState
    v-else
    :icon="LayoutGrid"
    :title="_('No units')"
    :message="unitTypes.length ? undefined : _('Add unit types first, then their units.')"
  />

  <Pagination :page="units" />
</template>
