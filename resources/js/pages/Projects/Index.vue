<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import Button from '@/components/ui/button/Button.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import useProjects from '@/store/useProjects';
import { Project } from '@/types';

defineProps<{
    projects: Project[];
}>();

const projectsStore = useProjects();

useBreadcrumbs().set([
    {
        title: 'Projects',
        href: route('projects.index'),
    },
]);
</script>

<template>
    <Head title="Projects" />

    <div v-for="project in projects" :key="project.id" class="m-3 space-y-4 rounded-lg border p-4 shadow-sm">
        <p class="text-sm text-muted-foreground">{{ project.name }}</p>
        <p class="text-sm text-muted-foreground">{{ project.status ? 'Active' : 'Inactive' }}</p>

        <Link
            :href="route('projects.show', project.id)"
            as="button"
            class="mr-2 inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
            prefetch
        >
            View Details
        </Link>
        <Button
            v-if="projectsStore.current?.id !== project.id"
            @click="projectsStore.setCurrent(project)"
            variant="secondary"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            {{ projectsStore.current ? 'Switch to this Project' : 'Select' }}
        </Button>
        <HeadingSmall v-else title="This is the currently selected project." />
    </div>
</template>
