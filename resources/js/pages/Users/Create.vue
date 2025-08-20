<script setup lang="ts">
import User from '@/components/forms/UserForm.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import useProjects from '@/store/useProjects';
import { Family, Project } from '@/types';

defineProps<{
    families: Family[];
}>();

const project = useProjects().current as Project;

useBreadcrumbs().set([
    {
        title: project.name,
        href: route('projects.show', project.id),
    },
    {
        title: 'Members',
        href: route('users.index'),
    },
]);
</script>

<template>
    <Head title="Members" />

    <UserForm class="mt-10">
        Register a new Member
    </UserForm>
</template>
