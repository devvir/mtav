<script setup lang="ts">
const model = defineModel<string>();

defineProps<{
  id?: string;
  name?: string;
  placeholder?: string;
  rows?: number;
  disabled?: boolean;
  required?: boolean;
}>();
</script>

<template>
  <textarea
    v-model="model"
    :id
    :name
    :placeholder
    :rows
    :disabled
    :required
    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
  />
</template>
