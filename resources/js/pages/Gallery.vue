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

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-2">
        <div class="relative min-h-[100vh] flex-1 rounded-xl md:min-h-min dark:border-sidebar-border">
            <div class="flex flex-wrap justify-around gap-6 m-8">
                <figure
                    v-for="(_, idx) in Array.from({ length: 20 })" :key="idx"
                    class="flex flex-col"
                >
                    <div class="rounded-2xl overflow-clip">
                        <img :src="`https://picsum.photos/640/420?${idx}`" class="w-full md:w-[640px] md:h-[420px]" />
                    </div>

                    <figcaption class="text-sm mt-2 space-y-0.5 max-w-[640px]">
                        <div class="font-extralight wrap-normal">Some description for this image goes here</div>
                        <div class="text-sm opacity-60">Posted by <span class="text-sidebar-foreground/60">&lt;some user&gt;</span></div>
                    </figcaption>
                </figure>
            </div>
        </div>
    </div>
</template>
