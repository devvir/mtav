<script setup lang="ts">
// Copilot - pending review
defineProps<{
  email: string;
  token: string;
}>();
</script>

<template>
  <AuthLayout title="Join MTAV">
    <template #description>
      <p class="mb-4">You're almost there! Please complete the form to activate your account.</p>
    </template>

    <form method="POST" action="/set-password" class="space-y-6">
      <input type="hidden" name="email" :value="email" />
      <input type="hidden" name="token" :value="token" />

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <div class="mt-1">
          <input
            id="password"
            name="password"
            type="password"
            required
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
        >
          Set Password
        </button>
      </div>
    </form>
  </AuthLayout>
</template>
