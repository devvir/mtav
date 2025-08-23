<script setup lang="ts">
import { User } from '@/types';

defineProps<{
    user?: User,
}>();
</script>

<template>
    <div class="p-10">
        <h1 class="mb-8 font-bold">
            <slot />
        </h1>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <form>
                <div class="mt-4">
                    <label class="block" for="email">Email</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-4 border-none block mt-1"
                        id="email" type="email" name="email" required />
                </div>

                <div class="mt-4">
                    <label class="block" for="firstname">First Name</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-4 border-none block mt-1"
                        id="firstname" name="firstname" required autofocus />
                </div>

                <div class="mt-4">
                    <label class="block" for="lastname">Last Name</label>
                    <input
                        class="w-full shadow-inner bg-gray-100 rounded-lg placeholder-black p-4 border-none block mt-1"
                        id="lastname" name="lastname" required autofocus />
                </div>
            </form>

            <aside class="">
                <div class="bg-gray-100 p-8 rounded">
                    <h2 class="font-bold text-2xl">Keep in mind</h2>
                    <ul class="list-disc mt-4 list-inside">
                        <li>All fields are required</li>
                        <li>Double-check the email, as it will serve to authenticate the user</li>
                        <li>An email with a link will be sent to them, to complete their registration</li>
                    </ul>
                </div>
            </aside>

        </div>
    </div>
</template>
