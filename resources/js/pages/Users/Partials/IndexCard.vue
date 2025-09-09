<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { User } from '@/types';

defineProps<{
    user: User;
}>();
</script>

<template>
    <Link :href="route('users.show', user.id)">
    <Card>
        <template #header>
            <div class="flex justify-between border-b border-gray-200 dark:border-gray-700">
                <img :src="user.avatar" alt="avatar" class="mr-6" />

                <div class="grid grid-rows-[2fr 1fr] text-right my-4" :title="user.name">
                    <div class="text-lg md:text-xl truncate">{{ user.name }}</div>

                    <Link v-if="user.family.name" :href="route('families.show', user.family.id)"
                        class="mt-1 text-xs text-gray-500/70 hover:text-gray-700/70 dark:hover:text-gray-300">
                    Family {{ user.family.name }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid gap-3 mt-3 text-sm mb-10">
            <div class="truncate">{{ user.email }}</div>
            <div class="truncate">{{ user.phone }}</div>
        </div>
    </Card>
    </Link>
</template>
