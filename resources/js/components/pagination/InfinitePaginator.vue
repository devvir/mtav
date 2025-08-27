<script setup lang="ts">
import { PaginatedResources } from '@/types';
import InfiniteScroll from './InfiniteScroll.vue';

defineProps<{
    loadable: string;
    list: PaginatedResources;
    params?: object;
}>();
</script>

<template>
    <div class="flex flex-wrap justify-center-safe gap-6 mx-8 my-6">
        <div v-if="! list.data.length" class="h-xl flex items-center">
            No results
        </div>

        <ul v-for="item in list.data" :key="item.id">
            <li class="h-full"><slot :item="item" /></li>
        </ul>
    </div>

    <InfiniteScroll :pagination="list" :loadable="loadable" :params="params" />
</template>
