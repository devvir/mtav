<script setup lang="ts">
import RankingList from '@/components/domain/RankingList.vue';
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { LotteryRun, ProjectEvent, Unit } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Clock, Dices, Home, LoaderCircle, TriangleAlert } from 'lucide-vue-next';

/**
 * The lottery as people live it: when it happens, the family's ranking (saved as it
 * changes), the outcome, and every invalidation with its reason.
 */
const props = defineProps<{
  lottery: ProjectEvent;
  status: 'running' | 'completed' | null;
  runs: LotteryRun[];
  family: { id: number; name: string; unit_type: string } | null;
  ranking: Unit[] | null;
  assignment:
    { id: number; name: string; unit: { id: number; identifier: string } | null }[] | null;
  canRank: boolean;
}>();

const ranking = ref<Unit[]>([...(props.ranking ?? [])]);
watch(
  () => props.ranking,
  (fresh) => (ranking.value = [...(fresh ?? [])]),
);

const saving = ref(false);
const save = useDebounceFn((units: Unit[]) => {
  saving.value = true;
  router.put(
    route('lottery.preferences'),
    { preferences: units.map((unit) => unit.id) },
    {
      preserveScroll: true,
      preserveState: true,
      only: ['ranking', 'flash'],
      onFinish: () => (saving.value = false),
    },
  );
}, 600);

const ownUnit = computed(
  () => props.assignment?.find((row) => row.id === props.family?.id)?.unit ?? null,
);
const invalidations = computed(() => props.runs.filter((run) => run.status === 'invalidated'));

useProjectChanges({
  lottery_runs: ['status', 'runs', 'assignment', 'canRank'],
  units: ['ranking'],
  unit_types: ['ranking'],
  families: ['family', 'ranking'],
});
</script>

<template>
  <PageHeader :title="_('Lottery')" :description="lottery.description">
    <template #info>
      <InfoTip :title="_('How the lottery works')" tour="member.ranking">
        <p>
          {{
            _(
              'Each family ranks the units of its type. The lottery first makes the worst-off family as well off as possible, then the whole project as satisfied as possible; only ties are drawn at random.',
            )
          }}
        </p>
        <p>
          {{
            _(
              'The result is final. If a run is ever invalidated, everyone sees who did it and why.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>

  <!-- Status -->
  <section
    data-tour="lottery.status"
    class="mb-6 rounded-2xl border-2 border-border bg-surface p-5"
  >
    <div v-if="status === 'completed'" class="flex items-start gap-3">
      <Home class="size-8 shrink-0 text-success" />
      <div>
        <h2 class="text-lg font-semibold">{{ _('The lottery is done') }}</h2>
        <p v-if="ownUnit" class="mt-1">
          {{ _('Your family received') }}
          <strong class="text-success">{{ ownUnit.identifier }}</strong
          >.
        </p>
      </div>
    </div>
    <div v-else-if="status === 'running'" class="flex items-center gap-3">
      <LoaderCircle class="size-8 shrink-0 animate-spin text-interactive" />
      <p class="text-lg font-semibold">
        {{ _('The lottery is running. The results appear here in a moment.') }}
      </p>
    </div>
    <div v-else class="flex items-start gap-3">
      <Clock class="size-8 shrink-0 text-interactive" />
      <div>
        <h2 class="text-lg font-semibold">
          <template v-if="lottery.start_date"
            >{{ _('Scheduled for') }} <DateTime :value="lottery.start_date"
          /></template>
          <template v-else>{{ _('Not scheduled yet') }}</template>
        </h2>
        <p class="mt-1 text-text-muted">
          {{ _('Until then, every family can change its ranking as often as it likes.') }}
        </p>
      </div>
    </div>
  </section>

  <!-- Invalidations are always visible: everyone must know a draw was undone, and why -->
  <section v-if="invalidations.length" class="mb-6 space-y-3">
    <article
      v-for="run in invalidations"
      :key="run.id"
      class="flex items-start gap-3 rounded-2xl border-2 border-warning/50 bg-warning-subtle p-4 text-warning-subtle-foreground"
    >
      <TriangleAlert class="size-6 shrink-0" />
      <div>
        <p class="font-semibold">
          {{ _('A lottery run was invalidated') }}
          <template v-if="run.invalidated_by">{{
            _('by {name}', { name: run.invalidated_by })
          }}</template>
          · <DateTime :value="run.invalidated_at" />
        </p>
        <p class="mt-1">«{{ run.invalidation_reason }}»</p>
      </div>
    </article>
  </section>

  <div class="grid gap-6 lg:grid-cols-2">
    <!-- The family's ranking -->
    <section data-tour="ranking.list" v-if="family && ranking.length">
      <div class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">{{ _("Your family's ranking") }}</h2>
        <span v-if="saving" class="flex items-center gap-1 text-sm text-text-muted"
          ><LoaderCircle class="size-4 animate-spin" /> {{ _('Saving') }}</span
        >
      </div>
      <p data-tour="ranking.saving" class="mb-3 text-sm text-text-muted">
        {{
          canRank
            ? _(
                '{type} units, most wanted first. Changes are saved as you make them, for the whole family.',
                { type: family.unit_type },
              )
            : _('{type} units, as your family ranked them.', { type: family.unit_type })
        }}
      </p>
      <RankingList v-model="ranking" :disabled="!canRank" @change="save" />
    </section>
    <section
      v-else-if="!family"
      class="rounded-2xl border-2 border-dashed border-border p-6 text-text-muted"
    >
      <Dices class="mb-2 size-8" />
      {{ _('Families rank units; you are not in a family of this project.') }}
    </section>

    <!-- Results -->
    <section v-if="assignment">
      <h2 class="mb-3 text-lg font-semibold">{{ _('Results') }}</h2>
      <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
        <li
          v-for="row in assignment"
          :key="row.id"
          class="flex items-center justify-between gap-3 px-4 py-3"
          :class="row.id === family?.id && 'bg-success-subtle'"
        >
          <span class="font-medium">{{ row.name }}</span>
          <Badge :tone="row.unit ? 'success' : 'neutral'">{{
            row.unit?.identifier ?? _('No unit')
          }}</Badge>
        </li>
      </ul>
    </section>
  </div>
</template>
