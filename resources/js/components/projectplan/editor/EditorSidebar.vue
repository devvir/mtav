<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Button } from '@/components/ui/button';
import { Undo, Redo, RotateCcw } from 'lucide-vue-next';

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
  <aside class="w-30 bg-card border-l border-border flex flex-col items-center py-4 gap-6">
    <!-- Actions Section -->
    <section class="flex flex-col items-center gap-2">
      <div class="text-xs text-muted-foreground px-2 mb-1">
        {{ _('Actions') }}
      </div>

      <!-- Undo -->
      <Button
        variant="ghost"
        size="sm"
        :disabled="!canUndo || processing"
        :title="_('Undo')"
        class="flex flex-col items-center gap-1 h-auto py-2 px-2"
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
        class="flex flex-col items-center gap-1 h-auto py-2 px-2"
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
        class="flex flex-col items-center gap-1 h-auto py-2 px-2"
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
