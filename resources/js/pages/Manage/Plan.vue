<script setup lang="ts">
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import PlanEditor from '@/components/plan/PlanEditor.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Plan } from '@/types/models';
import { router } from '@inertiajs/vue3';

defineProps<{ plan: Plan }>();

const editor = useTemplateRef<InstanceType<typeof PlanEditor>>('editor');
const processing = ref(false);

function save(payload: object) {
  processing.value = true;
  router.put(route('manage.plan.update'), payload as never, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => editor.value?.markSaved(),
    onFinish: () => (processing.value = false),
  });
}

/** Do not lose a drawing by leaving the page */
const unsaved = (event: BeforeUnloadEvent) => editor.value?.dirty && event.preventDefault();
onMounted(() => window.addEventListener('beforeunload', unsaved));
onBeforeUnmount(() => window.removeEventListener('beforeunload', unsaved));
const stopGuard = router.on('before', (event) => {
  if (
    editor.value?.dirty &&
    event.detail.visit.method === 'get' &&
    !window.confirm(_('Leave without saving the plan?'))
  ) {
    event.preventDefault();
  }
});
onBeforeUnmount(stopGuard);
</script>

<template>
  <PageHeader
    :title="_('Plan')"
    :description="_('Where each unit is, and what surrounds it. Everyone in the project sees it.')"
  >
    <template #info>
      <InfoTip :title="_('Drawing the plan')" tour="manage.plan">
        <p>
          {{
            _(
              'Show people where each unit is, and what surrounds it: buildings, streets, green areas, on as many floors as needed.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>
  <PlanEditor ref="editor" :plan :processing @save="save" />
</template>
