<script setup lang="ts">
import useProjects from '@/store/useProjects';
import { Project } from '@/types';
import { trans } from 'laravel-vue-i18n';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';

const project = useProjects().current as Project;

useBreadcrumbs().set([
    {
        title: project.name,
        href: route('projects.show', project.id),
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
