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

    <div class="flex flex-wrap-reverse justify-around gap-y-8 gap-x-6 my-8 mx-6">
        <figure v-for="idx in 20" :key="idx" class="flex-1 flex flex-col basis-1/4">
            <img :src="`https://picsum.photos/640/420?${idx}`" class="rounded-2xl overflow-clip" />

            <figcaption class="text-sm mt-2 space-y-0.5 max-w-[640px]">
                <div class="font-extralight wrap-normal">Some description for this image goes here</div>
                <div class="text-sm opacity-60">Posted by <span class="text-sidebar-foreground/60">&lt;some user&gt;</span></div>
            </figcaption>
        </figure>
    </div>
</template>
