<script setup lang="ts">
import InfiniteScroll from './InfiniteScroll.vue';

defineProps<{
    loadable: string;
    pagination: {
        current_page: number;
        last_page: number;
        next_page_url: number | null;
        path: string;
    };
    data?: object
}>();
</script>

<template>
    <slot />

    <InfiniteScroll :pagination="pagination" :loadable="loadable" :data="data" />
</template>
