<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { Redo, RotateCcw, Undo } from 'lucide-vue-next';

interface Props {
  canUndo: boolean;
  canRedo: boolean;
  hasChanges: boolean;
  processing?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
  undo: [];
  redo: [];
  reset: [];
}>();
</script>

<template>
  <aside class="flex w-30 flex-col items-center gap-6 border-l border-border bg-card py-4">
    <!-- Actions Section -->
    <section class="flex flex-col items-center gap-2">
      <div class="mb-1 px-2 text-xs text-muted-foreground">
        {{ _('Actions') }}
      </div>

      <!-- Undo -->
      <Button
        variant="ghost"
        size="sm"
        :disabled="!canUndo || processing"
        :title="_('Undo')"
        class="flex h-auto flex-col items-center gap-1 px-2 py-2"
        @click="emit('undo')"
      >
        <Undo :size="20" />
        <span class="text-xs">{{ _('Undo') }}</span>
      </Button>

      <!-- Redo -->
      <Button
        variant="ghost"
        size="sm"
        :disabled="!canRedo || processing"
        @click="emit('redo')"
        class="flex h-auto flex-col items-center gap-1 px-2 py-2"
        :title="_('Redo')"
      >
        <Redo :size="20" />
        <span class="text-xs">{{ _('Redo') }}</span>
      </Button>

      <!-- Reset -->
      <Button
        variant="ghost"
        size="sm"
        :disabled="!hasChanges || processing"
        @click="emit('reset')"
        class="flex h-auto flex-col items-center gap-1 px-2 py-2"
        :title="_('Reset All')"
      >
        <RotateCcw :size="20" />
        <span class="text-xs">{{ _('Reset') }}</span>
      </Button>
    </section>

    <!-- Spacer -->
    <div class="flex-1" />

    <!-- Future sections will go here -->
    <!-- New Items Section (Stage 4) -->
    <!-- Legend/Help Section (Future) -->
  </aside>
</template>
