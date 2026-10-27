<script setup lang="ts">
import MediaGrid from '@/components/domain/MediaGrid.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Media, Paginated } from '@/types/models';
import { Link, usePage } from '@inertiajs/vue3';
import { ImagePlus, Images } from 'lucide-vue-next';

const props = defineProps<{
  media: Paginated<Media>;
  category: 'visual' | 'audio' | 'document';
  filters: { q: string | null };
  storage: { used_mb: number; limit_mb: number } | null;
}>();

const canUpload = computed(() => usePage().props.project?.can.upload);

const tabs = computed(() => [
  { value: 'visual', label: _('Photos and videos') },
  { value: 'document', label: _('Documents') },
  { value: 'audio', label: _('Audio') },
]);

useProjectChanges({ media: ['media', 'storage'] });

const tab = (active: boolean) =>
  cn(
    'min-h-11 rounded-lg px-4 py-2 font-medium whitespace-nowrap',
    active ? 'bg-interactive text-interactive-foreground' : 'hover:bg-surface-interactive-hover',
  );
</script>

<template>
  <PageHeader
    :title="_('Files')"
    :description="_('Photos, videos, documents and audio shared in the project.')"
  >
    <template v-if="canUpload" #actions>
      <Button as-child>
        <Link
          :href="
            route('media.create', {
              category: props.category === 'visual' ? undefined : props.category,
            })
          "
          ><ImagePlus /> {{ _('Share files') }}</Link
        >
      </Button>
    </template>
  </PageHeader>

  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <nav class="-mx-1 flex gap-1 overflow-x-auto px-1">
      <Link
        v-for="t in tabs"
        :key="t.value"
        :href="route('media.index', t.value === 'visual' ? {} : { category: t.value })"
        :class="tab(category === t.value)"
      >
        {{ t.label }}
      </Link>
    </nav>
    <SearchField :model-value="filters.q" class="sm:max-w-xs" />
  </div>

  <MediaGrid v-if="media.data.length" :media="media.data" />
  <EmptyState
    v-else
    :icon="Images"
    :title="_('Nothing here yet')"
    :message="canUpload ? _('Be the first to share something.') : undefined"
  />

  <Pagination :page="media" />

  <p v-if="storage" class="mt-6 text-sm text-text-muted">
    {{
      _('Storage used: {used} of {limit} MB (until the project is approved).', {
        used: storage.used_mb,
        limit: storage.limit_mb,
      })
    }}
  </p>
</template>
