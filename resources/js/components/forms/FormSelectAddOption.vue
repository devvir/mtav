<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  open: boolean;
  target: string;
  legend: string;
}>();
</script>

<template>
  <!-- bg-accent-foreground/15 -->
  <ModalLink
    :title="_(legend)"
    :href="route(target)"
    class="flex items-center justify-end bg-linear-to-r from-accent-foreground/10 to-background to-50% px-base tracking-wide text-foreground/70 uppercase backdrop-blur-3xl transition-all group-hover:from-accent/15 group-hover:to-background/60 group-hover:font-bold group-hover:text-foreground/85"
    :class="{ 'animate-[pulse_2s_ease-in-out_3]': open }"
  >
    {{ _(legend) }}
    <svg
      fill="currentColor"
      viewBox="0 0 24 24"
      stroke-width="2"
      stroke="currentColor"
      class="ml-3 size-7 text-current transition-all duration-2000 ease-linear group-hover:scale-120 group-hover:rotate-360 group-hover:text-foreground"
      :class="{ 'animate-[spin_1000ms_linear_6]': open }"
    >
      <line x1="12" y1="5" x2="12" y2="19"></line>
      <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
  </ModalLink>
</template>
