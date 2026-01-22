<script setup lang="ts">
import FormPage from '@/components/entities/FormPage.vue';
import { type FormServiceData } from '@/components/forms';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  form: FormServiceData;
}>();
</script>

<template>
  <FormPage :form />
</template>
