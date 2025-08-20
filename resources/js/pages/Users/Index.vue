<script setup lang="ts">
import InfiniteScroll from '@/components/InfiniteScroll.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import useProjects from '@/store/useProjects';
import { PaginatedResources, Project } from '@/types';
import IndexByMember from './Partials/IndexByMember.vue';
import IndexByFamily from './Partials/IndexByFamily.vue';
import TypeFilterLink from './Partials/TypeFilterLink.vue';
import { onBeforeMount } from 'vue';

const props = defineProps<{
    resource: PaginatedResources;
    grouped: boolean;
}>();

// onRenderTriggered
onBeforeMount(() => window.history.replaceState({}, '', props.resource.path));


const project = useProjects().current as Project;

useBreadcrumbs().set([
    {
        title: project.name,
        href: route('projects.show', project.id),
    },
    {
        title: props.grouped ? 'Families' : 'Members',
        href: route('users.index'),
    },
]);
</script>

<template>
    <Head :title="grouped ? 'Families' : 'Members'" />

    <div class="flex items-center justify-between mx-4 my-2 py-2">
        <div>Search (TODO)</div>
        <div class="flex justify-around m-2 p-0.5 bg-sidebar-accent rounded-xl text-base border border-card">
            <TypeFilterLink side="left" :active="grouped" :grouped="true">Families</TypeFilterLink>
            <TypeFilterLink side="right" :active="! grouped" :grouped="false">Members</TypeFilterLink>
        </div>
    </div>

    <IndexByFamily v-if="grouped" :resource="resource" />
    <IndexByMember v-else         :resource="resource" />

    <InfiniteScroll :pagination="resource" loadable="resource" :data="{ grouped }" />
</template>
