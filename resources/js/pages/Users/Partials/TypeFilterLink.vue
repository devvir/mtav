<script setup lang="ts">

const props = defineProps<{
    active: boolean;
    grouped: boolean;
    side: 'left' | 'right';
}>();

const roundedClass = props.side === 'left' ? 'rounded-l-xl' : 'rounded-r-xl';
</script>

<template>
    <Link as="button"
        class="block py-1 px-3 disabled:cursor-default"
        :href="route('users.index')"
        :class="[roundedClass, { 'bg-sidebar hover:bg-accent cursor-pointer': ! active }]"
        :data="{ grouped }"
        :disabled="active"
        prefetch
    ><slot /></Link>
</template>
