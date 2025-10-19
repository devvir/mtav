<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import { currentProject } from '@/composables/useProjects';
import IndexCard from './Crud/IndexCard.vue';

defineProps<{
  projects: ApiResources<Project>;
  q: string;
}>();
</script>

<template>
  <Head title="Projects" />

  <Breadcrumbs global>
    <Breadcrumb route="projects.index" text="Projects" />
  </Breadcrumbs>

  <InfinitePaginator :list="projects" loadable="projects" :filter="q" :featured="currentProject?.id">
    <template v-slot:default="{ item }">
      <IndexCard :project="item as Required<(typeof projects.data)[0]>" class="mx-auto max-w-2xl" />
    </template>
  </InfinitePaginator>

  <InfiniteScroll :pageSpecs="projects" loadable="projects" />
</template>
