<script setup lang="ts">
import CallToAction from '@/components/ui/button/CallToAction.vue';
import { User } from '@/types';

defineProps<{
    admin: User;
}>();
</script>

<template>
    <div class="flex-1 p-5 md:min-w-[450px] max-w-[600px] rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400">
        <Link :href="route('users.show', admin.id)">
            <div class="flex justify-between items-center-safe gap-8">
                <div class="flex justify-start gap-5">
                    <img :src="admin.avatar" alt="avatar" />
                    <div class="flex flex-col items-start justify-center-safe" :title="admin.name">
                        <div class="text-xl truncate">{{ admin.name }}</div>
                        <div class="text-xs truncate">{{ admin.email }}</div>
                    </div>
                </div>

                <CallToAction variant="default" :href="route('admins.contact', admin.id)">
                    Contact
                </CallToAction>
            </div>
        </Link>
    </div>
</template>
