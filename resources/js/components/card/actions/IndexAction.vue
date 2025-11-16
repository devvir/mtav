<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { List } from 'lucide-vue-next';
import * as exposed from '../exposed';

const routes = inject(exposed.routes) as Record<ResourceAction, string>;
</script>

<template>
  <Button variant="ghost" as-child>
    <Link :href="route(routes.index)" class="flex items-center gap-3">
      <List class="size-[1.2em]" />
      <span class="@max-sm:hidden">{{ _('List') }}</span>
    </Link>
  </Button>
</template>
