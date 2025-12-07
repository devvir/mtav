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
      title="Admins"
      :list="[
        'You may assign Admins for the new Project at this point or later',
        'You may select Admins from the dropdown list',
        'If you prefer to invite a new Admin, email and firstname are required',
      ]"
    />
  </FormPage>
</template>
