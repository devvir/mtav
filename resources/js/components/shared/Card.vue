<template>
    <div class="flex-1 h-full px-6 py-4 rounded-2xl shadow-md/50 shadow-blue-300 bg-sidebar border-t border-t-blue-400">
        <slot name="header" class="border-b border-gray-200 dark:border-gray-700" />

        <slot />
    </div>
</template>
