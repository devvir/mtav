<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { can } from '@/composables/useAuth';
import ShowWrapper from '../shared/ShowWrapper.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
defineProps<{ project: Required<ApiResource<Project>> }>();
</script>

<template>
  <Head title="Project" />

  <Breadcrumbs global>
    <Breadcrumb v-if="can.viewAny('projects')" route="projects.index" text="Projects" />
    <Breadcrumb route="projects.show" :params="project.id">{{ project.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal panelClasses="modalPanel close-left backdrop-blur-lg">
    <ShowWrapper>
      <IndexCard :project="project" class="w-full border-0 shadow-none" />
    </ShowWrapper>
  </MaybeModal>
</template>
