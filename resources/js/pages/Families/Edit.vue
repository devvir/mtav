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
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    },
    {
        title: 'Families',
        href: route('families.index'),
    },
    {
        title: props.family.name,
        href: route('families.show', props.family.id),
    },
]);
</script>

<template>
    <Head title="Update Family" />
</template>
