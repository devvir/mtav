<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { User } from '@/types';

withDefaults(defineProps<{
    user: User;
    isAdmin?: boolean;
}>(), { isAdmin: false });
</script>

<template>
    <Link :href="route('users.show', user.id)">
        <Card class="min-w-72 max-w-96">
            <template #header>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700">
                    <div class="my-6">
                        <img :src="user.avatar" alt="avatar" />
                    </div>
                    <div class="flex flex-col items-end justify-center-safe" :title="user.name">
                        <div class=" max-w-44 text-lg truncate">{{ user.name }}</div>

                        <Link
                            v-if="user.family.name"
                            :href="route('families.show', user.family.id)"
                            class="mt-1 text-xs text-gray-500/70 hover:text-gray-700/70 dark:hover:text-gray-300"
                        >Family {{ user.family.name }}</Link>
                    </div>
                </div>
            </template>

            <div class="flex flex-col justify-between flex-wrap gap-3 mt-3 text-sm mb-10">
                <div>{{ user.email }}</div>
                <div>{{ user.phone }}</div>
            </div>
        </Card>
    </Link>
</template>
