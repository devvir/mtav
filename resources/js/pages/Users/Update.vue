<script setup lang="ts">
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-1">
        <div
            v-for="family in families.sort((a, b) => (a.name < b.name ? -1 : 1))" :key="family.id"
            class="flex-space-between m-3 flex flex-col space-y-4 rounded-lg border p-4 shadow-sm"
        >
            <div class="text-center">
                <div class="text-sm mb-2 text-gray-300">Family</div>
                <div class="text-2xl">{{ family.name  }}</div>
            </div>

            <div class="flex flex-col justify-between flex-wrap">
                <Link
                    v-for="member in family.members?.sort((a, b) => (a.name < b.name ? -1 : 1))" :key="member.id"
                    class="m-2 px-5 py-1.5
                        text-sm leading-normal text-[#1b1b18]
                        border border-none hover:border-[#1915014a] hover:shadow-sm
                        dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]
                        cursor-pointer"
                    :href="route('users.show', member.id)"
                >
                    <p class="text-sm text-muted-foreground">{{ member.name }}</p>
                </Link>
            </div>
        </div>
    </div>
</template>
