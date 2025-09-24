<script setup lang="ts">
defineProps<{
    error?: string;
}>();
</script>

<template>
    <div class="relative">
        <div
            class="absolute -top-1 w-full rounded-b-md bg-danger-foreground px-2 pt-1 text-sm text-white/80 transition-all empty:invisible"
            :class="{ 'scale-0 opacity-0': !error }"
        >
            {{ error }}
        </div>
    </div>
</template>
