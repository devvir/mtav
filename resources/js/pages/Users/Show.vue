<script setup lang="ts">
import DeleteUser from '@/pages/Users/Partials/DeleteUser.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { getCurrentProject } from '@/composables/useProjects';
import { User } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
}>();

const currentProject = getCurrentProject();

useBreadcrumbs().set([
    {
        title: currentProject.value?.name,
        route: 'projects.show',
        params: currentProject.value?.id,
    },
    {
        title: 'Members',
        route: 'users.index',
    },
    {
        title: props.user.name,
        route: 'users.show',
        params: props.user.id,
    },
]);
</script>

<template>
    <Head :title="user.name" />

    <div class="flex flex-wrap justify-center-safe align-middle gap-6 mx-8 h-full">
        <div class="my-auto px-6 py-4 max-w-[800px] rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400">
            <div class="flex justify-between items-center-safe px-3 py-5 border-b border-gray-200 dark:border-gray-700" :title="user.name">
                <div class="my-6"><img :src="user.avatar" alt="avatar" width="96" /></div>
                <div>
                    <div class="text-2xl truncate">{{ user.name }}</div>
                    <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                </div>
            </div>

            <div class="flex flex-col justify-between mt-6 mb-3 px-3 py-5 gap-3 border-b border-gray-200 dark:border-gray-700">
                <div class="text-sm mb-6 space-y-4">
                    <div>TODO</div>
                    <div>family basics</div>
                    <div>project (iff no project selected) with stats</div>
                    <div>online status and last activity</div>
                </div>

                <Link
                    v-if="user.family" :href="route('families.show', user.family.id)"
                    class="text-sm text-muted-foreground hover:text-gray-700 dark:hover:text-gray-300"
                    >
                    Family: {{ user.family?.name }}
                </Link>

                <div class="mt-4 text-sm text-muted-foreground">Created: {{ user.created_ago }}</div>
            </div>

            <div class="text-sm text-muted-foreground">Phone: {{ user.phone ?? 'N/A' }}</div>

            <div class="flex flex-col justify-between mt-24 mb-4 rounded-md hover:border border-red-500">
                <DeleteUser v-if="user.allows?.delete" :user="user" />
            </div>
        </div>
    </div>
</template>
