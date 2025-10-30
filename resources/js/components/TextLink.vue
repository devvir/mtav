<script setup lang="ts">
import { Method } from '@inertiajs/core';

interface Props {
    href: string;
    tabindex?: number;
    method?: Method;
    as?: string;
}

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="text-text-link underline underline-offset-4 transition-colors duration-200 ease-out hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
    >
        <slot />
    </Link>
</template>
