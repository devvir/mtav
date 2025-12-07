<script setup lang="ts">
import { type FormServiceData, FormAside } from '@/components/forms';
import FormPage from '@/components/entities/FormPage.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  form: FormServiceData;
}>();
</script>

<template>
  <FormPage :form>
    <FormAside
      title="Event Guidelines"
      :list="[
        'Project Members only see published Events',
        'RSVP allows members to confirm their attendance',
      ]"
    />
  </FormPage>
</template>
