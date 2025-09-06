<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';

const props = withDefaults(defineProps<{
    q?: string;
    autofocus?: boolean;
}>(), { q: '', autofocus: true});

const search = ref(props.q);

watchDebounced(
    search,
    (q: string) => router.reload({ data: { q } }),
    { debounce: 300, maxWait: 1000 }
);

</script>

<template>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mx-3 md:mx-8 mt-10 mb-8 py-2 gap-5">
        <slot name="left" />
        <div class="flex-1">
            <input
                :autofocus="autofocus"
                v-model.trim="search"
                class="w-full px-6 py-2 focus:shadow-md/70 shadow-blue-500 outline-0"
                placeholder="Search..."
            />
        </div>

        <slot name="right" />
    </div>
</template>
