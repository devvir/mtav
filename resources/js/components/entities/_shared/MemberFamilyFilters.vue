<script setup lang="ts">
import { Filters, Switch, SEARCH } from '@/components/filtering';

type BINARY = '0' | '1';

const props = defineProps<{
  grouped: boolean;
}>();

const model = ref<BINARY>(props.grouped ? '1' : '0');
const routes = { '0': 'members.index', '1': 'families.index' };
const options = { '0': 'Members', '1': 'Families' };

watch(model, (value: BINARY) => router.visit(route(routes[value])));
</script>

<template>
  <Filters :config="{ q: { type: SEARCH } }" auto-filter>
    <Switch :options v-model='model' />
  </Filters>
</template>
