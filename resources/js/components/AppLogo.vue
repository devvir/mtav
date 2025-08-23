<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex size-8 items-center justify-center rounded-xl overflow-clip">
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm text-sidebar-primary-foreground">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ $page.props.name }}</span>
    </div>
</template>
