<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { WhenVisible } from '@inertiajs/vue3';

const props = defineProps<{
  loadable: string;
  pageSpecs: ApiDataPage;
  params?: object;
}>();

const activateLoadMore = ref(false);
const pendingResults = computed(() => props.pageSpecs.current_page < props.pageSpecs.last_page);

// Make sure next page is laoded even if the last loaded page does not fill the viewport
onMounted(() => (activateLoadMore.value = pendingResults.value));
router.on('start', () => (activateLoadMore.value = false));
router.on('finish', () => (activateLoadMore.value = pendingResults.value));

// Keep the query string clean of pagination and search parameters
watchEffect(() => window.history.replaceState({}, '', props.pageSpecs.path));
</script>

<template>
  <WhenVisible
    v-if="activateLoadMore"
    :params="{ only: [loadable], data: { page: pageSpecs.current_page + 1, ...(params ?? {}) } }"
    :always="pendingResults"
    :buffer="600"
  />

  <div v-if="pendingResults && pageSpecs.current_page > 1" class="my-5 flex justify-center">
    <span class="text-xs">{{ _('Loading...') }}</span>
  </div>
</template>
