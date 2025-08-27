<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { Family, PaginatedFamilies, Project } from '@/types';
import { ComputedRef } from 'vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import AjaxSearch from '@/components/forms/AjaxSearch.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import IndexCard from './Partials/IndexCard.vue';

defineProps<{
    families: PaginatedFamilies;
    q: string;
}>();

const currentProject = getCurrentProject() as ComputedRef<Project>;

useBreadcrumbs().set([
    {
        title: currentProject.value?.name,
        route: 'projects.show',
        params: currentProject.value?.id,
    },
    {
        title: 'Families',
        route: 'families.index',
    },
]);
</script>

<template>
    <Head title="Families" />

    <AjaxSearch :q="q">
        <template #right>
            <div class="flex justify-around p-0.5 bg-sidebar-accent rounded-xl text-base border border-card">
                <MembersFamiliesSwitch side="left" :active="true" route-name="families.index">Families</MembersFamiliesSwitch>
                <MembersFamiliesSwitch side="right" :active="false" route-name="users.index">Members</MembersFamiliesSwitch>
            </div>
        </template>
    </AjaxSearch>

    <InfinitePaginator :list="families" loadable="families" :params="{ q }">
        <template v-slot:default="{ item }">
            <IndexCard :family="item as Family" />
        </template>
    </InfinitePaginator>
</template>
