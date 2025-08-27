<script setup lang="ts">
import { router, WhenVisible } from '@inertiajs/vue3';
import { computed, onMounted, ref, watchEffect } from 'vue';

const props = defineProps<{
    loadable: string;
    pagination: {
        current_page: number;
        last_page: number;
        next_page_url: number | null;
        path: string;
    };
    data?: object
}>();

const activateLoadMore = ref(false);
const pendingResults = computed(() => props.pagination.current_page < props.pagination.last_page);

// Make sure next page is laoded even if the last loaded page does not fill the viewport
onMounted(() => activateLoadMore.value = pendingResults.value);
router.on('start', () => activateLoadMore.value = false);
router.on('finish', () => activateLoadMore.value = pendingResults.value);

// Keep the query string clean of pagination and search parameters
watchEffect(() => window.history.replaceState({}, '', props.pagination.path));
</script>

<template>
    <slot />

    <WhenVisible
        v-if="activateLoadMore"
        :params="{ only: [ loadable ], data: { page: pagination.current_page + 1, ...(data ?? {}) } }"
        :always="pendingResults"
        :buffer="100"
    />

    <div v-if="pendingResults" class="flex justify-around my-5">
        <span class="text-xs">Loading...</span>
    </div>
</template>
