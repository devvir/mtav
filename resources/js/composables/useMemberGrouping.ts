const page = usePage();

const listMembersByFamily = () => computed<boolean>(() => page.props?.state.groupMembers !== false);
const listMembersUngrouped = () => computed<boolean>(() => page.props?.state.groupMembers === false);

export { listMembersByFamily, listMembersUngrouped };
