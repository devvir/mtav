import { BreadcrumbItem } from '@/types';
import { defineStore } from 'pinia';
import { ComputedRef } from 'vue';

type ConditionalBreadcrumItem = {
    title: string | null | undefined;
    href?: string;
    route?: string;
    params?: any;
    if?: ComputedRef<boolean>;
}

export default defineStore('breadcrumbs', {
    state: () => ({
        items: [] as BreadcrumbItem[],
    }),

    actions: {
        set(breadcrumbs: ConditionalBreadcrumItem[]) {
            this.items = breadcrumbs
                .filter(item => item.title && (item.href || item.route))
                .filter(item => ! item.if || item.if.value)
                .map(item => ({
                    title: item.title,
                    href: item.href ?? route(item.route, item.params ?? undefined)
                })) as BreadcrumbItem[];
        },
    },

    getters: {
        list: (state) => state.items,
    },
});
