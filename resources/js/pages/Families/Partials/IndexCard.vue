<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { Family } from '@/types';

defineProps<{
    family: Family;
}>();
</script>

<template>
    <Card class="min-w-62 lg:min-w-96 xl:min-w-72 max-w-[800px] xl:max-w-96">
        <Link
            :href="route('families.show', family.id)"
            class="flex justify-end items-center-safe px-3 py-5 border-b border-gray-200 dark:border-gray-700"
            :title="family.name"
        >
            <div class="mr-2 text-sm text-gray-300 dark:text-gray-600">Family</div>
            <div class="text-xl truncate">{{ family.name }}</div>
        </Link>

        <div class="flex flex-col justify-between mt-6 mb-3 gap-1">
            <Link
                v-for="member in family.members" :key="member.id"
                class="p-1 text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-sidebar-accent rounded-2xl cursor-pointer"
                :href="route('users.show', member.id)"
                :title="member.name"
                prefetch="click"
            >
                <div class="flex justify-start items-center-safe gap-3">
                    <img :src="member.avatar" alt="avatar" width="40px" />
                    <div class="max-w-32 text-sm truncate" :title="member.name">
                        {{ member.name }}
                    </div>
                </div>
            </Link>
        </div>
    </Card>
</template>
