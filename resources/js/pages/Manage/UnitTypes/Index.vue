<script setup lang="ts">
import EmptyState from '@/components/feedback/EmptyState.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _, _n } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Layers, Pencil, Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ unitTypes: UnitType[] }>();

async function remove(type: UnitType) {
  if (
    await confirm({
      title: _('Remove {name}?', { name: type.name }),
      confirmLabel: _('Remove'),
      danger: true,
    })
  ) {
    router.delete(route('manage.unit_types.destroy', type.id), { preserveScroll: true });
  }
}

useProjectChanges({ unit_types: ['unitTypes'], units: ['unitTypes'], families: ['unitTypes'] });
</script>

<template>
  <PageHeader
    :title="_('Unit types')"
    :description="_('Groups of equivalent units. Each family competes only for units of its type.')"
  >
    <template #info>
      <InfoTip :title="_('What is a unit type?')" tour="manage.unit_types">
        <p>
          {{
            _(
              "A group of equivalent units, like '2 bedrooms'. Each family has one type, and competes only for units of that type: each type is, in fact, its own lottery.",
            )
          }}
        </p>
      </InfoTip>
    </template>
    <template #actions>
      <Button data-tour="unit_types.add" as-child
        ><Link :href="route('manage.unit_types.create')"
          ><Plus /> {{ _('Add a unit type') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <ul
    data-tour="unit_types.list"
    v-if="unitTypes.length"
    class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface"
  >
    <li
      v-for="type in unitTypes"
      :key="type.id"
      class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center"
    >
      <div class="min-w-0 flex-1">
        <p class="font-semibold">{{ type.name }}</p>
        <p class="text-sm text-text-muted">
          {{
            [
              _n('{count} unit', '{count} units', type.units_count ?? 0),
              _n('{count} family', '{count} families', type.families_count ?? 0),
            ].join(', ')
          }}
          <span
            v-if="type.units_count !== type.families_count"
            class="font-medium text-warning-subtle-foreground"
          >
            · {{ _('not balanced') }}</span
          >
        </p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" as-child
          ><Link :href="route('manage.units.create', { unit_type: type.id })"
            ><Plus /> {{ _('Units') }}</Link
          ></Button
        >
        <Button
          variant="ghost"
          size="icon"
          as-child
          :aria-label="_('Edit {name}', { name: type.name })"
          ><Link :href="route('manage.unit_types.edit', type.id)"><Pencil /></Link
        ></Button>
        <Button
          variant="ghost"
          size="icon"
          :aria-label="_('Remove {name}', { name: type.name })"
          :disabled="Boolean(type.units_count || type.families_count)"
          @click="remove(type)"
          ><Trash2
        /></Button>
      </div>
    </li>
  </ul>
  <EmptyState
    v-else
    :icon="Layers"
    :title="_('No unit types yet')"
    :message="_('Start here: for example 2 bedrooms, 3 bedrooms.')"
  >
    <Button as-child
      ><Link :href="route('manage.unit_types.create')">{{ _('Add the first one') }}</Link></Button
    >
  </EmptyState>
</template>
