<script setup lang="ts">
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Media } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { FileText, Music, Play } from 'lucide-vue-next';

defineProps<{ media: Media[] }>();

const icons = { audio: Music, document: FileText };
</script>

<template>
  <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
    <li v-for="item in media" :key="item.id">
      <Link
        :href="route('media.show', item.id)"
        class="group relative block aspect-square overflow-hidden rounded-xl border-2 border-border bg-surface-sunken focus-visible:ring-2 focus-visible:ring-focus-ring"
      >
        <img
          v-if="item.thumbnail"
          :src="item.thumbnail"
          :alt="item.alt_text ?? item.description"
          loading="lazy"
          class="size-full object-cover transition group-hover:scale-105"
        />
        <span
          v-else
          class="flex size-full flex-col items-center justify-center gap-2 p-3 text-center"
        >
          <component
            :is="icons[item.category as 'audio' | 'document'] ?? FileText"
            class="size-10 text-text-subtle"
          />
          <span class="line-clamp-3 text-sm font-medium">{{ item.description }}</span>
        </span>
        <span
          v-if="item.category === 'video'"
          class="absolute inset-0 flex items-center justify-center"
        >
          <span class="flex size-12 items-center justify-center rounded-full bg-black/55 text-white"
            ><Play class="size-6 translate-x-0.5"
          /></span>
        </span>
        <span class="sr-only">{{ _('Open {name}', { name: item.description }) }}</span>
      </Link>
    </li>
  </ul>
</template>
