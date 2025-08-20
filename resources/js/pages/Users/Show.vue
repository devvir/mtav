<script setup lang="ts">
import DeleteUser from '@/pages/Users/Partials/DeleteUser.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import useProjects from '@/store/useProjects';
import { Project, User } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
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
    {
        title: props.user.name ?? `User #${props.user.id}`,
        href: route('users.show', props.user.id),
    },
]);
</script>

<template>
    <Head :title="user.name" />

    <div class="flex flex-wrap gap-3 m-5">
        <div class="flex-2 space-y-4 rounded-lg border p-4 shadow-sm min-w-2/3 lg:min-w-1/2">
            <p>{{ user.name }}</p>
            <p class="text-sm text-muted-foreground">{{ user.email }}</p>
            <p class="mt-10 text-sm text-muted-foreground">Phone: {{ user.phone ?? 'N/A' }}</p>
            <p class="text-sm text-muted-foreground">Age: TODO</p>
            <p class="text-sm text-muted-foreground">Family: TODO</p>
            <!-- TODO : Avatar -->
            <p class="text-sm text-muted-foreground">Joined at: TODO</p>
        </div>

        <DeleteUser v-if="user.allows?.delete" :user="user" />
    </div>
</template>
