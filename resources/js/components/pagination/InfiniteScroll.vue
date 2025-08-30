<script setup lang="ts">
import { PaginationSpec } from '@/types';
import { router, WhenVisible } from '@inertiajs/vue3';
import { computed, onMounted, ref, watchEffect } from 'vue';

const props = defineProps<{
    loadable: string;
    pagination: PaginationSpec;
    params?: object
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
    <WhenVisible
        v-if="activateLoadMore"
        :params="{ only: [ loadable ], data: { page: pagination.current_page + 1, ...(params ?? {}) } }"
        :always="pendingResults"
        :buffer="600"
    />

    <div v-if="pendingResults" class="flex justify-center my-5">
        <span class="text-xs">Loading...</span>
    </div>
</template>
