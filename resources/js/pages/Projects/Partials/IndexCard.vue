<script setup lang="ts">
import { getCurrentUser } from '@/composables/useAuth';
import { getCurrentProject } from '@/composables/useProjects';
import { Project, User } from '@/types';
import { computed, ComputedRef } from 'vue';

// TODO : investigate why I needed to make this a computed property
// Without it: 1. select project 2. enter details 3. go back to Projects index
//             The result is that it still shows as if no project was selected
// When wrapping this inside a computed(), it fixes the issue. But why?
// Notice that getCurrentProject already returns a computed property :shrug
const currentProject = computed(() => getCurrentProject().value);
const currentUser = getCurrentUser() as ComputedRef<User>;

defineProps<{
    project: Project;
}>();
</script>

<template>
    <div class="m-3 space-y-4 rounded-lg border p-4 shadow-sm">
        <p class="text-sm text-muted-foreground">{{ project.name }}</p>
        <p v-if="! currentUser.is_admin" class="text-sm text-muted-foreground">{{ project.status ? 'Active' : 'Inactive' }}</p>

        <Link
            :href="route('projects.show', project.id)"
            as="button"
            class="mr-2 inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
            prefetch
        >
            View Details
        </Link>
        <Link
            v-if="currentProject?.id !== project.id"
            :href="route('setCurrentProject', project.id)"
            method="POST"
            variant="button"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
            {{ currentProject ? 'Switch to this Project' : 'Select' }}
        </Link>

        <div v-else class="mt-4 text-base">
            This is the currently selected project.
        </div>
    </div>
</template>
