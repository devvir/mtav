<template>
    <div class="h-full px-6 py-2 rounded-2xl
                shadow-sm/50 shadow-blue-900 bg-blue-500/5 dark:bg-green-100/3 border-t border-blue-600/20 dark:border-blue-600/50">
        <slot name="header" class="border-b border-gray-200 dark:border-gray-700" />

        <slot />
    </div>
</template>
