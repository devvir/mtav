<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';

defineProps<{
  resource: ApiResource;
  routeName: string;
}>();
</script>

<template>
  <ModalLink
    v-if="resource.allows?.update"
    :href="route(routeName, resource.id)"
    paddingClasses="p-8"
    class="edit-button inline-block shrink-0 rounded-lg bg-surface-interactive p-3 ring-2 ring-border transition-all hover:bg-surface-interactive-hover hover:ring-border-strong focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0"
    :title="_('Edit')"
    @click.stop
  >
    <Edit3Icon class="size-5" />
  </ModalLink>
</template>

<style scoped>
.edit-button {
  align-self: start;
}
.im-modal-container .edit-button {
  align-self: end;
}
</style>
