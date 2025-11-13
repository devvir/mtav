<script setup lang="ts">
import { ModalLink } from '@inertiaui/modal-vue';
import { Button } from '@/components/ui/button';
import { Edit } from 'lucide-vue-next';
import * as exposed from '../exposed';
import { _ } from '@/composables/useTranslations';

const resource = inject(exposed.resource) as ApiResource;
const routes = inject(exposed.routes) as Record<ResourceAction, string>;
</script>

<template>
  <Button variant="ghost" as-child>
    <ModalLink
      slideover
      :href="route(routes.edit, resource.id)"
      class="flex items-center gap-3"
    >
      <Edit class="size-[1.2em]" />
      <span>{{ _('Edit') }}</span>
    </ModalLink>
  </Button>
</template>