<script setup lang="ts">
import { Button } from '@/components/ui/button';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import * as exposed from '../exposed';
import { _ } from '@/composables/useTranslations';

const resource = inject(exposed.resource) as ApiResource;
const routes = inject(exposed.routes) as Record<ResourceAction, string>;

const confirmationModalOpen = ref(false);

const expectedConfirmationText = computed(() => {
  return (resource as any).email?.trim()
    || (resource as any).name?.split(' ').slice(0, 2).join(' ').trim()
    || _('Confirm');
});

const toggleConfirmationState = (open: boolean) => {
  const addRemove = open ? 'add' : 'remove';
  const inertiaUI = document.querySelectorAll('.im-dialog');

  inertiaUI.forEach(modal => modal.classList[addRemove]('hidden'));
console.log(open);
  confirmationModalOpen.value = open;
}

const deleteResource = () => {
  router.delete(route(routes.destroy, resource.id), {
    preserveScroll: true,
  });

  confirmationModalOpen.value = false;
};
</script>

<template>
    <Button
      variant="ghost"
      v-bind="$attrs"
      class="flex items-center gap-3"
      @click.prevent="toggleConfirmationState(true)"
    >
      <Trash2 class="size-[1.2em] text-red-600" />
      <span>{{ _('Delete') }}</span>
    </Button>

    <ConfirmationModal
      v-model:open="confirmationModalOpen"
      :title="_('Are you sure you want to proceed?')"
      :description="_('This action cannot be undone. Please confirm by typing the required text below.')"
      :expected-text="expectedConfirmationText"
      :confirm-button-text="_('Delete')"
      variant="destructive"
      @confirm="deleteResource"
      @update:open="toggleConfirmationState"
    />
</template>