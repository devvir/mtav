<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import Field from '@/components/form/Field.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm as ask, prompt } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { LotteryRun, ProjectEvent } from '@/types/models';
import { router, useForm } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, Dices, LoaderCircle, Undo2 } from 'lucide-vue-next';

/**
 * Running the lottery: schedule, readiness per unit type, the run, and every run's
 * record (including invalidations, which everyone in the project reads).
 */
const props = defineProps<{
  lottery: ProjectEvent;
  status: 'running' | 'completed' | null;
  readiness: { id: number; name: string; units_count: number; families_count: number }[];
  runs: LotteryRun[];
  confirm: { option: string; message: string } | null;
}>();

const local = (iso: string | null) => {
  if (!iso) return '';
  const date = new Date(iso);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

const schedule = useForm({
  description: props.lottery.description,
  start_date: local(props.lottery.start_date),
});
const saveSchedule = () =>
  schedule
    .transform((data) => ({
      ...data,
      start_date: data.start_date ? new Date(data.start_date).toISOString() : null,
    }))
    .put(route('manage.lottery.schedule'), { preserveScroll: true });

const balanced = computed(() =>
  props.readiness.every((type) => type.units_count === type.families_count),
);
const due = computed(
  () => Boolean(props.lottery.start_date) && new Date(props.lottery.start_date!) <= new Date(),
);

async function run(options: string[] = []) {
  const ok = await ask({
    title: _('Run the lottery now?'),
    message: _(
      'Every family receives a unit from its ranking. The result is final; only an invalidation, with a reason everyone reads, can undo it.',
    ),
    typeToConfirm: _('RUN'),
    confirmLabel: _('Run the lottery'),
  });
  if (ok) router.post(route('manage.lottery.run'), { options }, { preserveScroll: true });
}

/** The server asks for an explicit confirmation (e.g. more units than families) */
watch(
  () => props.confirm,
  async (request) => {
    if (!request) return;
    const ok = await ask({
      title: _('Before running'),
      message: request.message,
      confirmLabel: _('Run anyway'),
    });
    if (ok)
      router.post(
        route('manage.lottery.run'),
        { options: [request.option] },
        { preserveScroll: true },
      );
  },
  { immediate: true },
);

async function invalidate(run: LotteryRun) {
  const reason = await prompt({
    title: _('Invalidate the lottery?'),
    message: _(
      'Every assignment is cleared and the lottery can run again. Everyone in the project will read who did it and why.',
    ),
    label: _('Why it is invalidated'),
    multiline: true,
    minLength: 10,
    confirmLabel: _('Invalidate'),
    danger: true,
  });
  if (reason)
    router.post(route('manage.lottery.invalidate', run.id), { reason }, { preserveScroll: true });
}

const tones = {
  running: 'info',
  completed: 'success',
  failed: 'error',
  invalidated: 'warning',
} as const;
const labels = computed(() => ({
  running: _('Running'),
  completed: _('Completed'),
  failed: _('Failed'),
  invalidated: _('Invalidated'),
}));

useProjectChanges({
  lottery_runs: ['status', 'runs'],
  units: ['readiness'],
  families: ['readiness'],
  unit_types: ['readiness'],
});
</script>

<template>
  <PageHeader :title="_('Lottery')">
    <template #info>
      <InfoTip :title="_('Running the lottery')" tour="manage.lottery">
        <p>
          {{
            _(
              'Once its date arrives, you run the lottery and every family receives a unit from its ranking, fairly and in one go. The result is final, and recorded.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>

  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Schedule -->
    <form
      data-tour="lottery.schedule"
      class="space-y-5 rounded-2xl border-2 border-border bg-surface p-5"
      @submit.prevent="saveSchedule"
    >
      <h2 class="text-lg font-semibold">{{ _('When and what') }}</h2>
      <Field
        :label="_('Date')"
        :hint="_('The lottery can run from this moment on.')"
        :error="schedule.errors.start_date"
        v-slot="field"
      >
        <TextInput
          v-bind="field"
          name="start_date"
          v-model="schedule.start_date"
          type="datetime-local"
          :disabled="Boolean(status)"
        />
      </Field>
      <Field :label="_('Description')" :error="schedule.errors.description" v-slot="field">
        <TextArea
          v-bind="field"
          name="description"
          v-model="schedule.description"
          rows="3"
          :disabled="Boolean(status)"
        />
      </Field>
      <div class="flex justify-end">
        <Button type="submit" :disabled="schedule.processing || Boolean(status)">{{
          _('Save')
        }}</Button>
      </div>
    </form>

    <!-- Readiness and run -->
    <section
      data-tour="lottery.readiness"
      class="space-y-4 rounded-2xl border-2 border-border bg-surface p-5"
    >
      <h2 class="text-lg font-semibold">{{ _('Readiness') }}</h2>
      <ul class="space-y-2">
        <li v-for="type in readiness" :key="type.id" class="flex items-center gap-3">
          <component
            :is="type.units_count === type.families_count ? CircleCheck : CircleAlert"
            :class="[
              'size-5',
              type.units_count === type.families_count ? 'text-success' : 'text-warning',
            ]"
          />
          <span class="flex-1">{{ type.name }}</span>
          <span class="text-sm text-text-muted">{{
            _('{units} units · {families} families', {
              units: type.units_count,
              families: type.families_count,
            })
          }}</span>
        </li>
      </ul>
      <p v-if="!readiness.length" class="text-text-muted">
        {{ _('Add unit types, units and families first.') }}
      </p>

      <div
        v-if="status === 'running'"
        class="flex items-center gap-2 rounded-xl bg-info-subtle p-3 text-info-subtle-foreground"
      >
        <LoaderCircle class="size-5 animate-spin" /> {{ _('The lottery is running.') }}
      </div>
      <div
        v-else-if="status === 'completed'"
        class="flex items-center gap-2 rounded-xl bg-success-subtle p-3 text-success-subtle-foreground"
      >
        <CircleCheck class="size-5" /> {{ _('The lottery is done: every family has its unit.') }}
      </div>
      <template v-else>
        <p v-if="!due" class="text-sm text-text-muted">
          {{
            lottery.start_date ? _('It can run once its date arrives.') : _('Set its date first.')
          }}
        </p>
        <p v-else-if="!balanced" class="text-sm text-warning-subtle-foreground">
          {{
            _('Some types have more units than families (or fewer): you will be asked to confirm.')
          }}
        </p>
        <Button class="w-full" :disabled="!due || !readiness.length" @click="run()"
          ><Dices data-tour="lottery.run" /> {{ _('Run the lottery') }}</Button
        >
      </template>
    </section>
  </div>

  <!-- Runs -->
  <section data-tour="lottery.runs" v-if="runs.length" class="mt-8">
    <h2 class="mb-3 text-lg font-semibold">{{ _('Runs') }}</h2>
    <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
      <li
        v-for="item in runs"
        :key="item.id"
        class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-start"
      >
        <div class="min-w-0 flex-1 space-y-1">
          <p class="flex flex-wrap items-center gap-2">
            <Badge :tone="tones[item.status]">{{ labels[item.status] }}</Badge>
            <DateTime :value="item.started_at" class="text-sm text-text-muted" />
            <span v-if="item.started_by" class="text-sm text-text-muted"
              >· {{ _('by {name}', { name: item.started_by }) }}</span
            >
          </p>
          <p v-if="item.failure" class="text-sm text-error">{{ item.failure }}</p>
          <p v-if="item.invalidation_reason" class="text-sm">
            {{ _('Invalidated by {name}:', { name: item.invalidated_by ?? '?' }) }} «{{
              item.invalidation_reason
            }}»
          </p>
        </div>
        <Button
          v-if="item.status === 'completed'"
          variant="outline"
          class="text-error"
          @click="invalidate(item)"
          ><Undo2 /> {{ _('Invalidate') }}</Button
        >
      </li>
    </ul>
  </section>
</template>
