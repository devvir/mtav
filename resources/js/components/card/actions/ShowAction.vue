<script setup lang="ts">
import { ModalLink } from '@inertiaui/modal-vue';
import { Eye } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import * as exposed from '../exposed';
import { _ } from '@/composables/useTranslations';

const resource = inject(exposed.resource) as ApiResource;
const routes = inject(exposed.routes) as Record<ResourceAction, string>;
</script>

<template>
  <Button variant="ghost" as-child>
    <ModalLink :href="route(routes.show, resource.id)" class="flex items-center gap-3">
      <Eye class="size-[1.2em]" />
      <span>{{ _('View') }}</span>
    </ModalLink>
  </Button>
</template>