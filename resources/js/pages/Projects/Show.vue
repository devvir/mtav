<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import Button from '@/components/ui/button/Button.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import useProjects from '@/store/useProjects';
import { Project } from '@/types';

const props = defineProps<{
    project: Project;
}>();

const projectsStore = useProjects();

const breadcrumbs = [];

if (props.project.allows?.viewAny) {
    breadcrumbs.push({
        title: 'Projects',
        href: route('projects.index'),
    });
}

breadcrumbs.push({
        title: props.project.name,
        href: route('projects.show', props.project.id),
});

useBreadcrumbs().set(breadcrumbs);
</script>

<template>
    <Head title="Project" />

    <div class="m-3 space-y-4 rounded-lg border p-4 shadow-sm">
        <p>{{ project.name }}</p>
        <p class="text-sm text-muted-foreground">Status: {{ project.status ? 'Active' : 'Inactive' }}</p>
        <p class="mt-10 text-sm text-muted-foreground">Users: TODO</p>
        <p class="text-sm text-muted-foreground">Admin(s): TODO</p>
        <p class="text-sm text-muted-foreground">Created at: {{ new Date(project.created_at).toLocaleDateString() }}</p>

        <Button
            v-if="projectsStore.current?.id !== project.id"
            @click="projectsStore.setCurrent(project, route('home'))"
            variant="secondary"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            {{ projectsStore.current ? 'Switch to this Project' : 'Select' }}
        </Button>
        <HeadingSmall v-else title="This is the currently selected project." class="text-sm" />
    </div>
</template>
