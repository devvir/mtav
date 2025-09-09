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

    <div class="flex flex-wrap justify-around gap-y-8 gap-x-6 my-4 md:my-8 max-2 md:mx-6">
        <figure v-for="idx in 20" :key="idx"
            class="flex-1 flex flex-col justify-center items-center p-2 md:p-4 box-border bg-green-900/5 dark:bg-white/3 rounded-3xl">
            <div class="flex flex-col justify-center min-w-[270px] lg:min-w-[600px]">
                <img :src="`https://picsum.photos/${Math.floor(Math.random() * 400) + 300}/${Math.floor(Math.random() * 400) + 300}?${idx}`"
                    class="rounded-2xl object-fit" />

                <figcaption class="text-sm space-y-0.5 mt-3 self-start">
                    <div class="font-extralight wrap-normal">Some description for this image goes here</div>
                    <div class="text-sm opacity-60">Posted by <span class="text-sidebar-foreground/60">&lt;some
                            user&gt;</span></div>
                </figcaption>
            </div>
        </figure>
    </div>
</template>
