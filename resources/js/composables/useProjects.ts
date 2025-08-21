import { Project } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ComputedRef } from 'vue';

const page = usePage();

export const getCurrentProject = (): ComputedRef<Project | null> => {
    return computed(() => page.props.state.project);
}

export const setCurrentProject = (project: Project): void => {
    router.post(route('setCurrentProject', project.id));
}

