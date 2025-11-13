<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { List } from 'lucide-vue-next';
import * as exposed from '../exposed';
import { _ } from '@/composables/useTranslations';

const routes = inject(exposed.routes) as Record<ResourceAction, string>;
</script>

<template>
  <Button variant="ghost" as-child>
    <Link :href="route(routes.index)" class="flex items-center gap-3">
      <List class="size-[1.2em]" />
      <span>{{ _('List') }}</span>
    </Link>
  </Button>
</template>