<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { Project, User } from '@/types';
import { ComputedRef } from 'vue';

const props = defineProps<{
    admin: User,
}>();

const currentProject = getCurrentProject() as ComputedRef<Project>;

useBreadcrumbs().set([
    {
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    },
    {
        title: 'Contact Admin',
        href: route('admins.contact', props.admin.id),
    },
]);
</script>

<template>
    <Head title="Contact Admin" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <div class="flex h-full justify-center">
                <h1 class="m-auto text-5xl">TO-DO</h1>
            </div>
        </div>
    </div>
</template>
