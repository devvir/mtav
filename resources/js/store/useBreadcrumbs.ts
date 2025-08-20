import { BreadcrumbItem } from '@/types';
import { defineStore } from 'pinia';

export default defineStore('breadcrumbs', {
    state: () => ({
        items: [] as BreadcrumbItem[],
    }),

    actions: {
        set(breadcrumbs: BreadcrumbItem[]) {
            this.items = breadcrumbs;
        },
    },

    getters: {
        list: (state) => state.items,
    },
});
