import { setCookie, getCookie } from '@/lib/utils';

const groupMembers = ref<boolean>(getCookie('groupMembers') === 'true');

/**
 * Composable to manage whether to list families (grouped) or standalone members.
 */
export function useMemberGrouping() {
  function setMemberGrouping(value: boolean) {
    groupMembers.value = value;
    setCookie('groupMembers', value ? 'true' : 'false');
  }

  return {
    groupMembers: readonly(groupMembers),
    setMemberGrouping,
  };
}
