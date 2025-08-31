<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { PaginatedProjects, Project } from '@/types';
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import IndexCard from './Partials/IndexCard.vue';

defineProps<{
    projects: PaginatedProjects;
    q: string;
}>();

useBreadcrumbs().set([
    {
        title: 'Projects',
        href: route('projects.index'),
    },
]);
</script>

<template>
    <Head title="Projects" />

    <InfinitePaginator :list="projects" loadable="projects" :filter="q">
        <template v-slot:default="{ item }">
            <IndexCard :project="item as Project" />
        </template>
    </InfinitePaginator>

    <InfiniteScroll :pagination="projects" loadable="projects" />
</template>
