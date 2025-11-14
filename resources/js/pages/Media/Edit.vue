<script setup lang="ts">
// TODO: Implement media edit form

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Edit Media</h1>
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
      <p class="text-yellow-800">
        <strong>TODO:</strong> Implement media edit form
      </p>
      <ul class="mt-2 text-sm text-yellow-700">
        <li>• Description editing</li>
        <li>• Category selection</li>
        <li>• Preview current file</li>
        <li>• Replace file option</li>
      </ul>
    </div>
  </div>
</template>