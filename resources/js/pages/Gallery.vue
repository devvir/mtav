<script setup lang="ts">
import { getCurrentProject } from '@/composables/useProjects';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { Project, type BreadcrumbItem } from '@/types';
import { trans } from 'laravel-vue-i18n';
import { ComputedRef } from 'vue';

const currentProject = getCurrentProject() as ComputedRef<Project>;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: trans('Gallery'),
        href: route('gallery'),
    },
];

if (currentProject) {
    breadcrumbs.unshift({
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    });
}

useBreadcrumbs().set(breadcrumbs);
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
