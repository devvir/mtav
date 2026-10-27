<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { FileUp, X } from 'lucide-vue-next';

/**
 * Pick or drop files; shows what was chosen, each removable.
 */
const props = defineProps<{
  accept?: string;
  multiple?: boolean;
  maxFiles?: number;
  maxFileKb?: number;
  invalid?: boolean;
}>();

const files = defineModel<File[]>({ default: () => [] });
const input = useTemplateRef<HTMLInputElement>('input');
const dragging = ref(false);

function add(list: FileList | null) {
  if (!list) return;
  const picked = Array.from(list);
  files.value = props.multiple
    ? [...files.value, ...picked].slice(0, props.maxFiles ?? Infinity)
    : picked.slice(0, 1);
  if (input.value) input.value.value = '';
}

function remove(index: number) {
  files.value = files.value.filter((_, i) => i !== index);
}

const tooLarge = (file: File) =>
  props.maxFileKb !== undefined && file.size > props.maxFileKb * 1024;

const size = (bytes: number) =>
  bytes > 1024 * 1024
    ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;
</script>

<template>
  <div class="space-y-3">
    <button
      type="button"
      :class="
        cn(
          'flex w-full flex-col items-center gap-2 rounded-xl border-2 border-dashed border-border-strong bg-surface-sunken px-4 py-8 text-center transition',
          'hover:border-border-interactive focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none',
          dragging && 'border-border-interactive bg-selection/40',
          invalid && 'border-error',
        )
      "
      @click="input?.click()"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="
        dragging = false;
        add($event.dataTransfer?.files ?? null);
      "
    >
      <FileUp class="size-8 text-text-muted" />
      <span class="font-medium">{{
        multiple ? _('Choose files or drop them here') : _('Choose a file or drop it here')
      }}</span>
      <span v-if="maxFileKb" class="text-sm text-text-muted">
        {{ _('Up to {size} each', { size: size(maxFileKb * 1024) }) }}
      </span>
    </button>
    <input
      ref="input"
      type="file"
      class="sr-only"
      :accept
      :multiple
      tabindex="-1"
      @change="add(($event.target as HTMLInputElement).files)"
    />

    <ul v-if="files.length" class="divide-y divide-border-subtle rounded-lg border border-border">
      <li
        v-for="(file, index) in files"
        :key="`${file.name}-${index}`"
        class="flex items-center gap-3 px-3 py-2"
      >
        <span class="min-w-0 flex-1 truncate">{{ file.name }}</span>
        <span
          :class="
            cn('shrink-0 text-sm', tooLarge(file) ? 'font-medium text-error' : 'text-text-muted')
          "
        >
          {{ size(file.size) }}
        </span>
        <button
          type="button"
          class="-mr-1 flex size-9 items-center justify-center rounded-full text-text-muted hover:bg-surface-interactive-hover hover:text-text"
          :aria-label="_('Remove {name}', { name: file.name })"
          @click="remove(index)"
        >
          <X class="size-4" />
        </button>
      </li>
    </ul>
  </div>
</template>
