<script setup lang="ts">
import { Button } from '@/components/ui/button';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { router } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import * as exposed from '../exposed';
import { entityNS } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';

const resource = inject(exposed.resource) as ApiResource;
const entity = inject(exposed.entity) as AppEntity;

const handleRestore = () => {
  router.post(route(`${entityNS(entity)}.restore`, resource.id), {}, {
    preserveScroll: true,
  });
};
</script>

<template>
  <ConfirmationModal
    :title="_('Are you sure you want to proceed?')"
    :description="_('This will restore the item to its active state. Please confirm by typing the required text below.')"
    :resource="resource"
    :confirm-button-text="_('Restore')"
    @confirm="handleRestore"
  >
    <Button variant="ghost" v-bind="$attrs" class="flex items-center gap-3">
      <RotateCcw class="size-[1.2em]" />
      <span>{{ _('Restore') }}</span>
    </Button>
  </ConfirmationModal>
</template>