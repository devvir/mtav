<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { Project, User } from '@/types';
import { ComputedRef } from 'vue';

const props = defineProps<{
    member: User;
}>();

const currentProject = getCurrentProject() as ComputedRef<Project>;

useBreadcrumbs().set([
    {
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    },
    {
        title: 'Members',
        href: route('users.index'),
    },
    {
        title: props.member.name,
        href: route('users.show', props.member.id),
    },
]);
</script>

<template>
    <Head title="Update Member" />
</template>
