<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import IndexCard from './Partials/IndexCard.vue';

defineProps<{
  projects: PaginatedProjects;
  q: string;
}>();
</script>

<template>
  <Head title="Projects" />

  <Breadcrumbs global>
    <Breadcrumb route="projects.index" text="Projects" />
  </Breadcrumbs>

  <InfinitePaginator :list="projects" loadable="projects" :filter="q">
    <template v-slot:default="{ item }">
      <IndexCard :project="item as Project" />
    </template>
  </InfinitePaginator>

  <InfiniteScroll :pagination="projects" loadable="projects" />
</template>
