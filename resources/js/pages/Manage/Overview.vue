<script setup lang="ts">
import DateTime from '@/components/feedback/DateTime.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Circle, CircleCheck, ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
  counts: Record<'unit_types' | 'units' | 'families' | 'people' | 'invitations' | 'media', number>;
  checklist: Record<
    'unit_types' | 'units' | 'families' | 'members' | 'balanced' | 'scheduled' | 'lottery',
    boolean
  >;
  approval: {
    approved: boolean;
    requested_at: string | null;
    limits: Record<string, number> | null;
    usage: Record<string, number> | null;
  };
}>();

const project = computed(() => usePage().props.project!);

const steps = computed(() => [
  {
    done: props.checklist.unit_types,
    label: _('Add the unit types'),
    href: route('manage.unit_types.index'),
  },
  { done: props.checklist.units, label: _('Add the units'), href: route('manage.units.index') },
  {
    done: props.checklist.families,
    label: _('Register the families'),
    href: route('manage.families.index'),
  },
  {
    done: props.checklist.members,
    label: _('Invite their members'),
    href: route('manage.invitations.index'),
  },
  {
    done: props.checklist.balanced,
    label: _('Have as many units as families of each type'),
    href: route('manage.lottery.show'),
  },
  {
    done: props.checklist.scheduled,
    label: _('Schedule the lottery'),
    href: route('manage.lottery.show'),
  },
  {
    done: props.checklist.lottery,
    label: _('Run the lottery'),
    href: route('manage.lottery.show'),
  },
]);

const usage = computed(() =>
  props.approval.limits && props.approval.usage
    ? [
        {
          label: _('People'),
          used: props.approval.usage.members,
          limit: props.approval.limits.members,
        },
        {
          label: _('Families'),
          used: props.approval.usage.families,
          limit: props.approval.limits.families,
        },
        { label: _('Units'), used: props.approval.usage.units, limit: props.approval.limits.units },
        {
          label: _('File storage (MB)'),
          used: props.approval.usage.media_storage_mb,
          limit: props.approval.limits.media_storage_mb,
        },
      ]
    : [],
);

async function requestApproval() {
  if (
    await confirm({
      title: _('Ask for approval?'),
      message: _('A superadmin reviews the project; once approved, its limits are lifted.'),
      confirmLabel: _('Ask for approval'),
    })
  ) {
    router.post(route('manage.approval.request'), {}, { preserveScroll: true });
  }
}

useProjectChanges({
  unit_types: ['counts', 'checklist'],
  units: ['counts', 'checklist'],
  families: ['counts', 'checklist', 'approval'],
  people: ['counts', 'checklist', 'approval'],
  invitations: ['counts'],
  lottery_runs: ['checklist'],
});
</script>

<template>
  <PageHeader :title="_('Overview')" :description="project.name" />

  <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
    <section class="rounded-2xl border-2 border-border bg-surface p-5">
      <h2 class="mb-4 text-lg font-semibold">{{ _('Getting to the lottery') }}</h2>
      <ol class="space-y-1">
        <li v-for="(step, index) in steps" :key="index">
          <Link
            :href="step.href"
            :class="
              cn(
                'flex min-h-11 items-center gap-3 rounded-lg px-2 hover:bg-surface-interactive-hover',
                step.done && 'text-text-muted',
              )
            "
          >
            <component
              :is="step.done ? CircleCheck : Circle"
              :class="cn('size-6 shrink-0', step.done ? 'text-success' : 'text-text-subtle')"
            />
            <span :class="step.done && 'line-through'">{{ step.label }}</span>
          </Link>
        </li>
      </ol>
    </section>

    <aside class="space-y-6">
      <section class="grid grid-cols-2 gap-3">
        <div
          v-for="(value, key) in {
            unit_types: _('unit types'),
            units: _('units'),
            families: _('families'),
            people: _('people'),
            invitations: _('pending invitations'),
            media: _('files'),
          }"
          :key="key"
          class="rounded-2xl bg-surface-sunken p-4"
        >
          <p class="text-2xl font-bold">{{ counts[key as keyof typeof counts] }}</p>
          <p class="text-sm text-text-muted">{{ value }}</p>
        </div>
      </section>

      <section
        v-if="!approval.approved"
        class="rounded-2xl border-2 border-warning/50 bg-surface p-5"
      >
        <h2 class="flex items-center gap-2 font-semibold">
          <ShieldCheck class="size-5" /> {{ _('Awaiting approval') }}
        </h2>
        <p class="mt-1 text-sm text-text-muted">
          {{ _('Until a superadmin approves the project, it works within these limits:') }}
        </p>
        <ul class="mt-3 space-y-3">
          <li v-for="item in usage" :key="item.label">
            <div class="flex justify-between text-sm">
              <span>{{ item.label }}</span
              ><span class="text-text-muted">{{ item.used }} / {{ item.limit }}</span>
            </div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-sunken">
              <div
                :class="
                  cn(
                    'h-full rounded-full',
                    item.used / item.limit > 0.85 ? 'bg-warning' : 'bg-interactive',
                  )
                "
                :style="{ width: `${Math.min(100, (item.used / item.limit) * 100)}%` }"
              />
            </div>
          </li>
        </ul>
        <p v-if="approval.requested_at" class="mt-4 text-sm">
          {{ _('Approval requested') }} <DateTime :value="approval.requested_at" relative />.
        </p>
        <Button v-else class="mt-4 w-full" @click="requestApproval">{{
          _('Ask for approval')
        }}</Button>
      </section>
    </aside>
  </div>
</template>
