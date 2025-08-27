<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { Project, User } from '@/types';
import { getCurrentUser } from '@/composables/useAuth';
import { ComputedRef } from 'vue';
import AdminIndex from '../Admins/Partials/AdminIndex.vue';

const props = defineProps<{
    project: Project;
}>();

const currentProject = getCurrentProject();
const currentUser = getCurrentUser() as ComputedRef<User>;

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
        <p v-if="! currentUser.is_admin" class="mb-10 text-sm text-muted-foreground">Status: {{ project.status ? 'Active' : 'Inactive' }}</p>
        <p class="text-sm text-muted-foreground">Users: TODO</p>
        <p class="text-sm text-muted-foreground">Admin(s): TODO</p>
        <p class="text-sm text-muted-foreground">Created at: {{ new Date(project.created_at).toLocaleDateString() }}</p>

        <Link
            v-if="currentProject?.id !== project.id"
            :href="route('setCurrentProject', project.id)"
            method="POST"
            variant="button"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            {{ currentProject ? 'Switch to this Project' : 'Select' }}
        </Link>

        <div v-else class="mt-8 text-base">
            This is the currently selected project.
        </div>
    </div>

    <AdminIndex :admins="project.admins as User[]" />
</template>
