<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';

const props = withDefaults(defineProps<{
    q: string,
}>(), { q: '' });

const search = ref(props.q);

watchDebounced(
    search,
    () => router.reload({ data: { q: search.value.trim() } }),
    { debounce: 300, maxWait: 1000 }
);

</script>

<template>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mx-3 md:mx-8 my-5 py-2 gap-5">
        <slot name="left" />
        <div class="flex-1">
            <input
                v-model="search"
                class="w-full p-2 rounded-xl bg-blue-200 text-gray-900 text-md"
                placeholder="Search..."

            />
        </div>
        <slot name="right" />
    </div>
</template>
