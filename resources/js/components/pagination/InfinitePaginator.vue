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

    <div v-if="!list.data.length" class="w-full h-full flex justify-center items-center text-xl">
        No results
    </div>

    <TransitionGroup name="fade" tag="ul"
        class="flex flex-wrap justify-evenly gap-3 md:gap-4 xl:gap-6 mx-3 md:mx-5 xl:mx-8 my-6">
        <li v-for="item in list.data" :key="item.id" class="flex-1">
            <slot :item="item" />
        </li>
    </TransitionGroup>

    <InfiniteScroll :pagination="list" :loadable="loadable" :params="{ q: filter }" />
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.2s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0, 0);
}

.fade-leave-active {
    position: absolute;
}

.fade-move {
    transition: all 0.3s;
}
</style>