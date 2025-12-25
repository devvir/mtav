<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { EntityCard, CardHeader, CardFooter, CreatedMeta } from '@/components/card';
import { Button } from '@/components/ui/button';
import { Download, Eye } from 'lucide-vue-next';

const props = defineProps<{
  document: ApiResource<Media>;
}>();

// Disable link to ShowCard (document previews open in new tab)
const resource = { ...props.document, allows: { ...props.document.allows, view: false } };

const getExtensionIcon = (extension: string) => `/thumbnails/extensions/${extension}.png`;

const handlePreview = () => window.open(props.document.url, '_blank');

const handleDownload = () => {
  const link = document.createElement('a');
  link.href = props.document.url;
  link.download = props.document.description || 'document';
  link.click();
}

const canPreview = computed(() => {
  const mimeType = props.document.mime_type;

  return (
    mimeType === 'application/pdf' ||
    mimeType.startsWith('text/') ||
    mimeType === 'text/csv' ||
    mimeType === 'text/markdown'
  );
});
</script>

<template>
  <EntityCard :resource entity="media" type="index" no-link>
    <CardHeader
      :title="document.description"
      :kicker="`${_('Size')}: ${document.file_size_formatted}`"
    >
      <template #icon>
        <img
          :src="getExtensionIcon(document.extension)"
          :alt="document.category_label"
          :title="document.filetype"
          class="size-14"
        />
      </template>

      {{ document.alt_text }}
    </CardHeader>

    <CardFooter class="flex justify-between items-center">
      <div class="flex gap-2">
        <Button
          v-if="canPreview"
          variant="outline"
          size="sm"
          class="@md:-my-1"
          @click.prevent.stop="handlePreview"
          :title="_('Preview')"
        >
          <Eye class="size-4" />
        </Button>

        <Button
          variant="outline"
          size="sm"
          class="@md:-my-1"
          @click.prevent.stop="handleDownload"
          :title="_('Download')"
        >
          <Download class="size-4" />
        </Button>
      </div>
      <CreatedMeta :creator="document.owner" />
    </CardFooter>
  </EntityCard>
</template>
