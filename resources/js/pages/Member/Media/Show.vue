<script setup lang="ts">
import DateTime from '@/components/feedback/DateTime.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Media } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Download, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps<{ media: Media }>();

const back = computed(() => ({
  href: route(
    'media.index',
    props.media.category === 'image' || props.media.category === 'video'
      ? {}
      : { category: props.media.category },
  ),
  label: _('Files'),
}));

async function remove() {
  if (
    await confirm({
      title: _('Remove this file?'),
      message: props.media.description,
      confirmLabel: _('Remove'),
      danger: true,
    })
  ) {
    router.delete(route('media.destroy', props.media.id));
  }
}
</script>

<template>
  <PageHeader :title="media.description" :back>
    <template #actions>
      <Button variant="outline" as-child
        ><a :href="media.url" download><Download /> {{ _('Download') }}</a></Button
      >
      <Button v-if="media.can.update" variant="outline" as-child
        ><Link :href="route('media.edit', media.id)"><Pencil /> {{ _('Edit') }}</Link></Button
      >
      <Button v-if="media.can.delete" variant="outline" class="text-error" @click="remove"
        ><Trash2 /> {{ _('Remove') }}</Button
      >
    </template>
  </PageHeader>

  <div class="overflow-hidden rounded-2xl border-2 border-border bg-black/90">
    <img
      v-if="media.category === 'image'"
      :src="media.url"
      :alt="media.alt_text ?? media.description"
      class="mx-auto max-h-[75dvh] w-auto"
    />
    <video
      v-else-if="media.category === 'video'"
      :src="media.url"
      controls
      class="mx-auto max-h-[75dvh] w-full"
    />
    <div v-else-if="media.category === 'audio'" class="bg-surface p-6">
      <audio :src="media.url" controls class="w-full" />
    </div>
    <iframe
      v-else-if="media.mime_type === 'application/pdf'"
      :src="media.url"
      class="h-[75dvh] w-full bg-white"
      :title="media.description"
    />
    <div v-else class="bg-surface p-8 text-center">
      <Button as-child
        ><a :href="media.url" download><Download /> {{ _('Download the document') }}</a></Button
      >
    </div>
  </div>

  <p class="mt-3 text-sm text-text-muted">
    <template v-if="media.owner"
      >{{ _('Shared by {name}', { name: media.owner.name }) }} ·
    </template>
    <DateTime :value="media.created_at" />
  </p>
</template>
