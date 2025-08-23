<script setup lang="ts">
import { WhenVisible } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

const forceLoadMore = ref(false);
const pendingResults = computed(() => props.pagination.current_page < props.pagination.last_page);

watch(() => props.pagination, () => {
    window.history.replaceState({}, '', props.pagination.path);

    forceLoadMore.value = pendingResults.value;
    setTimeout(() => forceLoadMore.value = false, 500);
});
</script>

<template>
    <slot />

    <div v-if="forceLoadMore" class="h-screen"></div>

    <WhenVisible
        v-if="pendingResults"
        :params="{ only: [ loadable ], data: { page: pagination.current_page + 1, ...(data ?? {}) } }"
        :always="pendingResults"
        :buffer="600"
    >
        <template #fallback>
            <div class="flex justify-around my-5">
                <span class="text-xs">Loading...</span>
            </div>
        </template>
    </WhenVisible>
</template>
