<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { PaginatedUsers, User } from '@/types';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import AjaxSearch from '@/components/forms/AjaxSearch.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import IndexCard from './Partials/IndexCard.vue';

defineProps<{
    members: PaginatedUsers;
    q: string;
}>();

const currentProject = getCurrentProject();

useBreadcrumbs().set([
    {
        title: currentProject.value?.name,
        route: 'projects.show',
        params: currentProject.value?.id,
    },
    {
        title: 'Members',
        route: 'users.index',
    },
]);
</script>

<template>
    <Head title="Members" />

    <AjaxSearch :q="q">
        <template v-slot:right>
            <div class="flex p-0.5 bg-sidebar-accent rounded-xl text-base border border-card">
                <MembersFamiliesSwitch side="left" :active="false" route-name="families.index">Families</MembersFamiliesSwitch>
                <MembersFamiliesSwitch side="right" :active="true" route-name="users.index">Members</MembersFamiliesSwitch>
            </div>
        </template>
    </AjaxSearch>

    <InfinitePaginator :list="members" loadable="members" :params="{ q }">
        <template v-slot:default="{ item }">
            <IndexCard :user="item as User" />
        </template>
    </InfinitePaginator>
</template>
