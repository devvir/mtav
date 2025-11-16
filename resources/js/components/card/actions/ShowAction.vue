<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Eye } from 'lucide-vue-next';
import * as exposed from '../exposed';

const resource = inject(exposed.resource) as ApiResource;
const routes = inject(exposed.routes) as Record<ResourceAction, string>;
</script>

<template>
  <Button variant="ghost" as-child>
    <ModalLink :href="route(routes.show, resource.id)" class="flex items-center gap-3">
      <Eye class="size-[1.2em]" />
      <span class="@max-sm:hidden">{{ _('View') }}</span>
    </ModalLink>
  </Button>
</template>
