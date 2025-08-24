<script setup lang="ts">
import { PaginatedUsers, User } from '@/types';
import AdminCard from './AdminCard.vue';
import { computed } from 'vue';

const props = defineProps<{
    admins: PaginatedUsers | (User[] & { data?: undefined });
}>();

const list = computed(
    () => props.admins.data ?? props.admins
);
</script>


<template>
    <div class="flex flex-col gap-2 mx-8 my-4 rounded-xl bg-sidebar-accent">
        <div class="flex-1 flex justify-around mt-2 mx-2 p-2 text-lg text-white/70 bg-sidebar/40 rounded-xl">
            Admins
        </div>

        <div class="flex flex-wrap justify-center-safe gap-4 mx-6 my-4">
            <AdminCard v-for="admin in list" :key="admin.id" :admin="admin" />
        </div>
    </div>
</template>
