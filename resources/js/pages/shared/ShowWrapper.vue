<script setup lang="ts">
import { useModal } from '@inertiaui/modal-vue';
</script>

<template>
  <div class="mx-auto flex h-full w-full max-w-3xl flex-col items-center justify-center">
    <div class="flex flex-col" :class="useModal() ? 'w-full' : '@lg:min-w-md'">
      <slot />
    </div>
  </div>
</template>
