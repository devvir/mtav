<script setup lang="ts">

const props = defineProps<{
    active: boolean;
    routeName: string;
    side: 'left' | 'right';
}>();

const roundedClass = props.side === 'left' ? 'rounded-l-xl' : 'rounded-r-xl';
</script>

<template>
    <Link as="button"
        class="block w-full py-1 px-3 disabled:cursor-default"
        :href="route(routeName)"
        :class="[roundedClass, { 'bg-sidebar hover:bg-accent cursor-pointer': ! active }]"
        :disabled="active"
        prefetch
    ><slot /></Link>
</template>
