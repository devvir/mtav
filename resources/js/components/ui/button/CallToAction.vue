<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    href: string;
    method?: string;
}>();
</script>

<template>
    <Button>
        <Link :href="href" :method="method ?? 'GET'" class="cursor-pointer">
            <slot />
        </Link>
    </Button>
</template>
