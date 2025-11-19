<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { useModal, visitModal } from '@inertiaui/modal-vue';
import { Edit } from 'lucide-vue-next';
import * as exposed from '../exposed';

const resource = inject(exposed.resource, {}) as ApiResource;
const routes = inject(exposed.routes, {}) as Record<ResourceAction, string>;
const modal = useModal();

const openEditForm = () => {
  modal?.close();

  // TODO : This is hacky af, but InertiaUI closes the new modal too if I don't do it async...
  setTimeout(() => {
    visitModal(route(routes.edit, resource.id), { config: { slideover: true } });
  }, 500);
}
</script>

<template>
  <Button variant="ghost" as-child>
    <button
      slideover
      :href="route(routes.edit, resource.id)"
      class="flex items-center gap-3"
      @click.stop="openEditForm"
    >
      <Edit class="size-[1.2em]" />
      <span class="@max-sm:hidden">{{ _('Edit') }}</span>
    </button>
  </Button>
</template>
