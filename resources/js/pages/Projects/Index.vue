<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentUser } from '@/composables/useAuth';
import { getCurrentProject } from '@/composables/useProjects';
import { PaginatedProjects, User } from '@/types';
import { ComputedRef } from 'vue';
import InfiniteScroll from '@/components/InfiniteScroll.vue';

defineProps<{
    projects: PaginatedProjects;
}>();

const currentProject = getCurrentProject();
const currentUser = getCurrentUser() as ComputedRef<User>;

useBreadcrumbs().set([
    {
        title: 'Projects',
        href: route('projects.index'),
    },
]);
</script>

<template>
    <Head title="Projects" />

    <div v-for="project in projects.data" :key="project.id" class="m-3 space-y-4 rounded-lg border p-4 shadow-sm">
        <p class="text-sm text-muted-foreground">{{ project.name }}</p>
        <p v-if="! currentUser.is_admin" class="text-sm text-muted-foreground">{{ project.status ? 'Active' : 'Inactive' }}</p>

        <Link
            :href="route('projects.show', project.id)"
            as="button"
            class="mr-2 inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
            prefetch
        >
            View Details
        </Link>
        <Link
            v-if="currentProject?.id !== project.id"
            :href="route('setCurrentProject', project.id)"
            method="POST"
            variant="button"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            {{ currentProject ? 'Switch to this Project' : 'Select' }}
        </Link>

        <HeadingSmall v-else title="This is the currently selected project." />
    </div>

    <InfiniteScroll :pagination="projects" loadable="projects" />
</template>
