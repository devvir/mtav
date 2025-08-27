<template>
    <div class="flex-1 px-6 py-4 rounded-2xl shadow-lg/45 shadow-blue-400 bg-sidebar border-t border-t-blue-400">
        <slot name="header" class="border-b border-gray-200 dark:border-gray-700" />

        <slot />
    </div>
</template>
