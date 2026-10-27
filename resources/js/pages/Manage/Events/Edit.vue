<script setup lang="ts">
import EventForm from '@/components/forms/EventForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { ProjectEvent } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<{ event: ProjectEvent }>();

async function cancel() {
  if (
    await confirm({
      title: _('Cancel {title}?', { title: props.event.title }),
      message: _('The event is removed for everyone.'),
      confirmLabel: _('Cancel the event'),
      cancelLabel: _('Keep it'),
      danger: true,
    })
  ) {
    router.delete(route('manage.events.destroy', props.event.id));
  }
}
</script>

<template>
  <PageHeader
    :title="event.title"
    :back="{ href: route('manage.events.index'), label: _('Events') }"
  >
    <template #actions>
      <Button variant="outline" class="text-error" @click="cancel"
        ><Trash2 /> {{ _('Cancel the event') }}</Button
      >
    </template>
  </PageHeader>
  <EventForm :event />
</template>
