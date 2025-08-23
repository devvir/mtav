<script setup lang="ts">
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { PaginatedFamilies, Project } from '@/types';
import { ComputedRef } from 'vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import AjaxSearch from '@/components/forms/AjaxSearch.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';

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

    <InfinitePaginator :pagination="families" loadable="families" :data="{ q }">
        <div class="flex flex-wrap justify-center-safe gap-6 mx-8 my-6">
            <div v-if="! families.data.length" class="h-xl flex items-center">
                No results
            </div>
            <div
                v-for="family in families.data" :key="family.id"
                class="flex-1 px-6 py-4 min-w-62 lg:min-w-96 xl:min-w-72 max-w-[800px] xl:max-w-96 rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400"
            >
                <Link
                    :href="route('families.show', family.id)"
                    class="flex justify-end items-center-safe px-3 py-5 border-b border-gray-200 dark:border-gray-700"
                    :title="family.name"
                >
                    <div class="mr-2 text-sm text-gray-300 dark:text-gray-600">Family</div>
                    <div class="text-2xl truncate">{{ family.name }}</div>
                </Link>

                <div class="flex flex-col justify-between mt-6 mb-3 gap-1">
                    <Link
                        v-for="member in family.members" :key="member.id"
                        class="p-1 text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-sidebar-accent rounded-2xl cursor-pointer"
                        :href="route('users.show', member.id)"
                        :title="member.name"
                        prefetch="click"
                    >
                        <div class="flex justify-start items-center-safe gap-3">
                            <img :src="member.avatar" alt="avatar" width="40px" />
                            <div class=" text-sm truncate">{{ member.name }}</div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </InfinitePaginator>
</template>
