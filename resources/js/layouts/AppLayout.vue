<script setup lang="ts">
// import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';
import { usePage } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';

const page = usePage();
const breadcrumbs = useBreadcrumbs();

const flash = reactive(page.props.flash);

onMounted(
    () => setTimeout(() => flash.success = null, 3000)
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs.list">
        <slot />
    </AppLayout>

    <div
        v-if="flash.success"
        class="
            container fixed m-auto z-50 p-3 top-5 left-3 right-3 flex items-center align-middle rounded-4xl
            bg-green-200 cursor-pointer shadow-md shadow-dark-green-300 dark:shadow-green-100
        "
        @click="flash.success = null"
        title="Click to hide"
    >
        <div class="text-green-800 text-sm self-center m-auto">{{ flash.success }}</div>
    </div>
</template>
