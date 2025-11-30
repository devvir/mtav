<script setup lang="ts">
import { type FormServiceData } from '@/components/forms';
import FormAside from '@/components/forms/FormAside.vue';
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
        'Changes to the email remain on hold until the user confirms them',
        'Double-check the email, as it will serve to authenticate the User',
      ]"
    />
  </FormPage>
</template>
