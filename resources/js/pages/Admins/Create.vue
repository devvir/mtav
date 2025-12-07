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
      title="Keep in mind"
      :list="[
        'The Admin will be sent a link to complete their registration',
        'Double-check the email, as it will serve to authenticate the Admin',
      ]"
    />
  </FormPage>
</template>
