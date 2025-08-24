<script setup lang="ts">
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import UserForm from '@/components/forms/UserForm.vue';

const currentProject = getCurrentProject();

const breadcrumbs = [];

if (currentProject.value) {
    breadcrumbs.push({
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    });
};

useBreadcrumbs().set([...breadcrumbs, {
    title: 'Members',
    href: route('users.index'),
}, {
    title: 'Registration',
    href: route('users.create'),
}]);
</script>

<template>
    <Head title="Members" />

    <UserForm class="mt-10">
        Register a new Member
    </UserForm>
</template>
