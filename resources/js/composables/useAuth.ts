import { Auth, User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ComputedRef } from 'vue';

const page = usePage();

export const getCurrentUser = (): ComputedRef<User | null> => {
    return computed(() => page.props.auth.user);
}

export const getAuth = (): ComputedRef<Auth> => {
    return computed(() => page.props.auth);
}

