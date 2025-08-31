<script setup lang="ts">
import { PaginatedResources } from '@/types';
import InfiniteScroll from './InfiniteScroll.vue';
import IndexSearch from './IndexSearch.vue';

defineProps<{
    loadable: string;
    list: PaginatedResources;
    filter: string;
}>();
</script>

<template>
    <IndexSearch :q="filter">
        <template v-slot:right>
            <slot name="search-right" />
        </template>
    </IndexSearch>

    <div class="flex flex-wrap justify-center-safe gap-6 mx-8 my-6">
        <div v-if="! list.data.length" class="h-xl flex items-center">
            No results
        </div>

        <ul v-for="item in list.data" :key="item.id">
            <li class="h-full"><slot :item="item" /></li>
        </ul>
    </div>

    <InfiniteScroll :pagination="list" :loadable="loadable" :params="{ q: filter }" />
</template>
