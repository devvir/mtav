import { Project } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';

const page = usePage();

export default defineStore('projects', {
    state: () => ({
        projects: [],
        selected: page.props.state.project as Project | null,
    }),

    actions: {
        reset() {
            this.projects = [];
            this.selected = null;
        },

        async setCurrent(project: Project, redirectTo: string | null = null) {
            this.selected = project;

            router.post(route('setCurrentProject', project.id), { redirectTo });
        },
    },

    getters: {
        all: (state) => state.projects,
        current: (state) => state.selected,
    },
});
