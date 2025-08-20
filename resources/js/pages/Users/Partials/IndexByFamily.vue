<script setup lang="ts">
import { PaginatedFamilies } from '@/types';

const props = defineProps<{
    resource: PaginatedFamilies;
}>();
</script>

<template>
    <div class="grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-4 m-4">
        <div
            v-for="family in resource.data" :key="family.id"
            class="rounded-2xl p-3 bg-sidebar border-b border-r border-gray-200 dark:border-gray-900"
        >
            <div class="flex justify-end items-center-safe p-2 border-b border-gray-200 dark:border-gray-700" :title="family.name">
                <div class="mr-2 text-sm text-gray-300 dark:text-gray-600">Family</div>
                <div class="text-lg truncate">{{ family.name  }}</div>
            </div>

            <div class="flex flex-col justify-between flex-wrap mt-3">
                <Link
                    v-for="member in family.members" :key="member.id"
                    class="m-0.5 px-2 py-1 text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-sidebar-accent rounded-2xl cursor-pointer"
                    :href="route('users.show', member.id)"
                    :title="member.name"
                    prefetch="click"
                >
                    <div class="text-xs max-w-[210px] truncate">{{ member.name }}</div>
                </Link>
            </div>
        </div>
    </div>
</template>
