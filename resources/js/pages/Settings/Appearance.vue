<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Head from '@/components/Head.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
</script>

<template>
  <Head title="Appearance Settings" />

  <Breadcrumbs global>
    <Breadcrumb route="profile.edit" text="Settings" />
    <Breadcrumb route="appearance" text="Appearance Settings" />
  </Breadcrumbs>

  <SettingsLayout>
    <div class="space-y-6">
      <HeadingSmall
        title="Appearance Settings"
        description="Update your account's appearance settings"
      />
      <AppearanceTabs />
    </div>
  </SettingsLayout>
</template>
