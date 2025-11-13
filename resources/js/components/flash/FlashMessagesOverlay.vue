<script setup lang="ts">
import FlashMessages from './FlashMessages.vue';
import { useFlashMessages } from './useFlashMessages';

const { hasVisibleMessages } = useFlashMessages();
</script>

<template>
  <div
    v-if="hasVisibleMessages"
    class="absolute inset-0 -right-px z-10 flex items-center transition-opacity duration-300"
  >
    <div class="w-full bg-background/60 backdrop-blur-sm">
      <div class="flex justify-center px-8 py-4 @md:px-wide">
        <FlashMessages />
      </div>
    </div>
  </div>
</template>
