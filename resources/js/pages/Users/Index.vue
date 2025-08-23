<script setup lang="ts">
import InfiniteScroll from '@/components/InfiniteScroll.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { PaginatedUsers } from '@/types';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import AjaxSearch from '@/components/forms/AjaxSearch.vue';

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
        <template #right>
            <div class="flex justify-around p-0.5 bg-sidebar-accent rounded-xl text-base border border-card">
                <MembersFamiliesSwitch side="left" :active="false" route-name="families.index">Families</MembersFamiliesSwitch>
                <MembersFamiliesSwitch side="right" :active="true" route-name="users.index">Members</MembersFamiliesSwitch>
            </div>
        </template>
    </AjaxSearch>

    <div class="flex flex-wrap justify-center-safe gap-8 mx-8 my-6">
        <div
            v-for="member in members.data" :key="member.id"
            class="flex-1 px-6 py-4 min-w-72 max-w-96 rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400"
        >
            <Link :href="route('users.show', member.id)">
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700">
                    <div class="my-6">
                        <img :src="member.avatar" alt="avatar" />
                    </div>
                    <div class="flex flex-col items-end justify-center-safe" :title="member.name">
                        <div class="text-xl truncate">{{ member.name }}</div>

                        <Link
                            :href="route('families.show', member.family?.id)"
                             class="mt-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        >Family {{  member.family?.name }}</Link>
                    </div>
                </div>

                <div class="flex flex-col justify-between flex-wrap mt-3 gap-3 text-sm mb-10">
                    <div>{{  member.email }}</div>
                    <div>{{  member.phone }}</div>
                </div>
            </Link>
        </div>
    </div>

    <InfiniteScroll :pagination="members" loadable="members" :data="{ q }" />
</template>
