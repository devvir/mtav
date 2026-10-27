<script setup lang="ts">
import UnitTypeForm from '@/components/forms/UnitTypeForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <PageHeader
    :title="_('Add a unit type')"
    :back="{ href: route('manage.unit_types.index'), label: _('Unit types') }"
  />
  <UnitTypeForm />
</template>
