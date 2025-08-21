<script setup lang="ts">
import { getCurrentProject } from '@/composables/useProjects';
import { trans } from 'laravel-vue-i18n';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { Project } from '@/types';
import { ComputedRef } from 'vue';

const currentProject = getCurrentProject() as ComputedRef<Project>;

useBreadcrumbs().set([
    {
        title: currentProject.value.name,
        href: route('projects.show', currentProject.value.id),
    },
    {
        title: trans('Dashboard'),
        href: route('home'),
    },
]);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <PlaceholderPattern />
            </div>
        </div>
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
