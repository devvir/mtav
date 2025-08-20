<script setup lang="ts">
import { PaginatedUsers } from '@/types';

const props = defineProps<{
    resource: PaginatedUsers;
}>();
</script>

<template>
    <!-- <div class="grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-4 m-4"> -->
        <div class="flex flex-wrap justify-center gap-4 m-4">
            <div
            v-for="member in resource.data" :key="member.id"
            class="flex-1 min-w-52 max-w-96 rounded-2xl p-5 bg-sidebar border-b border-r border-gray-200 dark:border-gray-900"
            >
            <Link :href="route('users.show', member.id)">
                <div class="flex flex-col items-end justify-center-safe border-b border-gray-200 dark:border-gray-700" :title="member.name">
                    <div class="text-lg truncate">{{ member.name  }}</div>
                    <div class="mt-1 mb-2 text-xs text-gray-500">Family {{  member.family?.name }}</div>
                </div>

                <div class="flex flex-col justify-between flex-wrap mt-3 gap-3 text-xs">
                    <div>{{  member.email }}</div>
                    <div>{{  member.phone }}</div>
                </div>
            </Link>
        </div>
    </div>
</template>
