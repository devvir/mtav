<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';

defineProps<{
  admin: User;
}>();
</script>

<template>
  <Head title="Contact Admin" />

  <Breadcrumbs>
    <Breadcrumb route="admins.contact" :params="admin.id" text="Contact Admin" />
  </Breadcrumbs>

  <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="relative min-h-[100vh] flex-1 rounded-xl border border-border/70 md:min-h-min dark:border-border">
      <div class="flex h-full justify-center">
        <h1 class="m-auto text-5xl">TODO</h1>
      </div>
    </div>
  </div>
</template>
