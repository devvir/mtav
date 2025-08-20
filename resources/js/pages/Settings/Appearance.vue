<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import useBreadcrumbs from '@/store/useBreadcrumbs';

useBreadcrumbs().set([
    {
        title: 'Appearance settings',
        href: route('appearance'),
    },
]);
</script>

<template>
    <Head title="Appearance settings" />

    <SettingsLayout>
        <div class="space-y-6">
            <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />
            <AppearanceTabs />
        </div>
    </SettingsLayout>
</template>
