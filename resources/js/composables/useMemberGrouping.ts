import { usePage } from '@inertiajs/vue3';
import { computed, ComputedRef } from 'vue';

const page = usePage();

const listMembersByFamily = (): ComputedRef<boolean> => {
    return computed(() => page.props?.state.groupMembers !== false);
}

const listMembersUngrouped = (): ComputedRef<boolean> => {
    return computed(() => page.props?.state.groupMembers === false);
}

export {
    listMembersByFamily,
    listMembersUngrouped,
};



