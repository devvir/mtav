<script setup lang="ts">
import AppLogoIcon from '@/components/layout/AppLogoIcon.vue';
import { useSidebar } from './sidebar';

const { isMobile, open: isSidebarOpen } = useSidebar();

const responsiveClasses = computed(() => {
  if (isMobile.value) {
    return 'size-12 my-2 rounded-xl p-1';
  }

  return isSidebarOpen.value
    ? 'size-12 p-1 rounded-xl bg-surface-interactive'
    : 'rounded-sm bg-accent p-0.5 w-full scale-150 mt-2';
});
</script>

<template>
  <div class="flex items-center gap-4">
    <AppLogoIcon
      :class="responsiveClasses"
      class="overflow-clip border border-current fill-current transition-all"
    />

    <span v-if="isMobile || isSidebarOpen" class="flex-1 overflow-hidden">
      <div class="truncate text-xl font-bold tracking-wider text-text">{{ $page.props.name }}</div>
      <div class="text-xs leading-5 font-extrabold tracking-wider text-text-subtle uppercase">
        FIng <span class="opacity-50">&fallingdotseq;</span> UdelaR
      </div>
    </span>
  </div>
</template>
