<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8 space-y-0.5">
        <h2 class="text-xl font-semibold tracking-tight text-text">{{ title }}</h2>
        <p v-if="description" class="text-sm text-text-muted">
            {{ description }}
        </p>
    </div>
</template>
