<script setup lang="ts">
defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div
    class="flex aspect-video items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800"
  >
    <div class="text-center opacity-60">
      <svg
        class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <p class="mt-4 text-lg font-medium text-gray-500 dark:text-gray-400">
        {{ media.category.toUpperCase() }}
      </p>
      <p class="text-sm text-gray-400 dark:text-gray-500">
        {{ media.path.split('.').pop()?.toUpperCase() }}
      </p>
    </div>
  </div>
</template>
