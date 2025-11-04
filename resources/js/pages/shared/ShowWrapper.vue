<script setup lang="ts">
import { useModal } from '@inertiaui/modal-vue';
</script>

<template>
  <div class="flex h-full w-full flex-col items-center justify-center max-w-3xl mx-auto">
    <div class="flex flex-col" :class="useModal() ? 'w-full' : '@lg:min-w-md'">
      <slot />
    </div>
  </div>
</template>
