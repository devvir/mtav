<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { can } from '@/composables/useAuth';
import ShowWrapper from '../shared/ShowWrapper.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
defineProps<{ admin: ApiResource<Admin> }>();
</script>

<template>
  <Head title="Project" />

  <Breadcrumbs global>
    <Breadcrumb v-if="can.viewAny('admins')" route="admins.index" text="Admins" />
    <Breadcrumb route="admins.show" :params="admin.id">{{ admin.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal panelClasses="modalPanel backdrop-blur-lg">
    <ShowWrapper>
      <IndexCard :admin class="w-full rounded-b-none border-0 bg-transparent shadow-none" />
    </ShowWrapper>
  </MaybeModal>
</template>
