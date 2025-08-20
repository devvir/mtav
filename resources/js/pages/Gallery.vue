<script setup lang="ts">
import useProjects from '@/store/useProjects';
import { type BreadcrumbItem } from '@/types';
import { trans } from 'laravel-vue-i18n';

const project = useProjects().current;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: trans('Gallery'),
        href: '/gallery',
    },
];

if (project) {
    breadcrumbs.unshift({
        title: project.name,
        href: `/projects/${project.id}`,
    });
}
</script>

<template>
    <Head :title="trans('Gallery')" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <div class="flex h-full justify-center">
                <h1 class="m-auto text-5xl">TO-DO</h1>
            </div>
        </div>
    </div>
</template>
