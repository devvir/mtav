<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import { SEARCH, SWITCH } from '@/components/filtering';

const props = defineProps<{
  projects: ApiResources<Project>;
  all?: boolean;
  q?: string;
}>();

const filters = {
  q: { type: SEARCH, value: props.q },
  all: {
    type: SWITCH,
    options: { '0': 'Active', '1': 'All' },
    value: props.all === false ? '0' : '1',
  }
};
</script>

<template>
  <IndexPage entity="project" :resources="projects" :filters />
</template>
