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
    <div class="flex flex-col gap-2 m-4 rounded-xl bg-sidebar-accent/10">
        <div class="self-center mt-5 mx-2 py-2 px-6 text-lg text-white/70 rounded-xl">
            Admins
        </div>

        <div class="flex flex-wrap justify-center-safe gap-2 mx-6 mb-4 p-3">
            <AdminCard v-for="admin in list" :key="admin.id" :admin="admin" />
        </div>
    </div>
</template>
