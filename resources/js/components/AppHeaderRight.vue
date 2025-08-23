<script setup lang="ts">
import CallToAction from './ui/button/CallToAction.vue';
</script>

<template>
    <div v-if="$page.props.auth.user?.is_admin && route().current() !== 'users.create'">
        <CallToAction variant="default" :href="route('users.create')">
            Create User
        </CallToAction>
    </div>
</template>
