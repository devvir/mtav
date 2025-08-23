<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header>
        <h3 class="mb-1 text-xl font-medium">{{ title }}</h3>
        <p v-if="description" class="text-xs text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
