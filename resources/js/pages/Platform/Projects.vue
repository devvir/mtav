<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Paginated, Project } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';

type Row = Project & {
  people_count: number;
  creator: { id: number; email: string; name: string } | null;
};

defineProps<{
  projects: Paginated<Row>;
  counts: { awaiting: number };
  filters: { q: string | null; show: string };
}>();

const shows = computed(() => [
  { value: 'all', label: _('All') },
  { value: 'awaiting', label: _('Awaiting approval') },
  { value: 'unapproved', label: _('Not approved') },
  { value: 'approved', label: _('Approved') },
]);

async function approve(project: Row) {
  const ok = await confirm({
    title: _('Approve {project}?', { project: project.name }),
    message: _("You trust its admins: the project's limits are lifted."),
    confirmLabel: _('Approve'),
  });
  if (ok) router.put(route('admin.projects.approve', project.id), {}, { preserveScroll: true });
}

const tab = (active: boolean) =>
  cn(
    'min-h-11 rounded-lg px-4 py-2 font-medium whitespace-nowrap',
    active ? 'bg-interactive text-interactive-foreground' : 'hover:bg-surface-interactive-hover',
  );
</script>

<template>
  <PageHeader :title="_('Projects')" :description="_('Every project on the platform.')" />

  <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <nav class="-mx-1 flex gap-1 overflow-x-auto px-1">
      <Link
        v-for="item in shows"
        :key="item.value"
        :href="route('admin.projects.index', item.value === 'all' ? {} : { show: item.value })"
        :class="tab(filters.show === item.value)"
      >
        {{ item.label }}
        <span
          v-if="item.value === 'awaiting' && counts.awaiting"
          class="ml-1 rounded-full bg-warning px-2 text-xs font-bold text-warning-foreground"
          >{{ counts.awaiting }}</span
        >
      </Link>
    </nav>
    <SearchField :model-value="filters.q" class="lg:max-w-xs" />
  </div>

  <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
    <li
      v-for="project in projects.data"
      :key="project.id"
      class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center"
    >
      <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-2">
          <Link
            :href="route('projects.show', project.id)"
            class="font-semibold hover:text-text-link"
            >{{ project.name }}</Link
          >
          <Badge v-if="project.is_approved" tone="success">{{ _('Approved') }}</Badge>
          <Badge v-else-if="project.approval_requested_at" tone="warning">{{
            _('Asks for approval')
          }}</Badge>
          <Badge v-else>{{ _('Not approved') }}</Badge>
        </p>
        <p class="text-sm text-text-muted">
          {{
            _('{people} people · {families} families · {units} units', {
              people: project.people_count,
              families: project.families_count ?? 0,
              units: project.units_count ?? 0,
            })
          }}
          · <DateTime :value="project.created_at" :time="false" />
          <template v-if="project.creator">
            · {{ project.creator.name }} ({{ project.creator.email }})</template
          >
        </p>
      </div>
      <Button v-if="!project.is_approved" variant="outline" @click="approve(project)"
        ><ShieldCheck /> {{ _('Approve') }}</Button
      >
    </li>
  </ul>
  <p v-if="!projects.data.length" class="py-8 text-center text-text-muted">
    {{ _('No projects match.') }}
  </p>

  <Pagination :page="projects" />
</template>
