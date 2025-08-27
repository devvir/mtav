<script setup lang="ts">
import { User } from '@/types';
import Button from '../ui/button/Button.vue';

defineProps<{
    user?: User,
}>();

const createUser = () => null; // TODO
</script>

<template>
    <div class="flex flex-col p-6">
        <h1 class="mb-6 font-bold">
            <slot />
        </h1>

        <div class="flex flex-col xl:flex-row gap-4 text-base">
            <form id="users.create" class="flex-1 xl:min-w-1/2 p-4 text-indigo-900 space-y-4 border border-white/15 rounded-xl">
                <div>
                    <label class="text-white" for="email">Email</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-2 border-none mt-1"
                        id="email" type="email" name="email" required />
                </div>

                <div>
                    <label class="text-white" for="firstname">First Name</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-2 border-none mt-1"
                        name="firstname" required />
                </div>

                <div>
                    <label class="text-white" for="lastname">Last Name</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-2 border-none mt-1"
                        name="lastname" required />
                </div>

                <div>
                    <label class="text-white" for="family">Family</label>
                    <select
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-2 border-none mt-1"
                        name="family" required
                    >
                        <option value="" selected>New family</option>
                    </select>
                </div>
            </form>

            <aside class="flex flex-col justify-between gap-8 h-full lg:h-auto opacity-60 bg-sidebar-accent p-8 rounded-2xl mt-12 xl:mt-0">
                <h2 class="font-bold text-2xl">Keep in mind</h2>
                <ul class="list-disc list-inside text-lg space-y-2">
                    <li>All fields are required</li>
                    <li>Double-check the email, as it will serve to authenticate the user</li>
                    <li>An email will be sent, with a link to complete the registration</li>
                </ul>

                <Button class="p-0 rounded-xl">
                    <Link
                        as="button" form="users.create" method="POST" :href="route('users.store')"
                        class="cursor-pointer w-full h-full"
                        @submit.prevent="createUser"
                    >
                        Create
                    </Link>
                </Button>
            </aside>
        </div>
    </div>
</template>
