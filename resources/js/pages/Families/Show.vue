<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { Family, Project } from '@/types';
import { ComputedRef } from 'vue';

const props = defineProps<{
    family: Family;
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
    {
        title: props.family.name,
        route: 'families.show',
        params: props.family.id,
    },
]);
</script>

<template>
    <Head :title="family.name" />

    <div class="flex flex-wrap justify-center-safe align-middle gap-6 mx-8 h-full">
        <div class="flex-1 px-6 py-4 max-w-[600px] my-auto rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400">
            <div class="flex justify-end items-center-safe px-3 py-5 border-b border-gray-200 dark:border-gray-700" :title="family.name">
                <div class="mr-2 text-sm text-gray-300 dark:text-gray-600">Family</div>
                <div class="text-2xl truncate">{{ family.name }}</div>
            </div>

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
</template>
