<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';

const breadcrumbs = useBreadcrumbs();

</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs.list" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
