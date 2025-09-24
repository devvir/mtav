const page = usePage();

export const auth = computed(() => page.props.auth);
export const currentUser = computed<User | null>(() => auth.value.user);

export const iAmAdmin = computed<boolean>(() => auth.value?.user?.is_admin ?? false);
export const iAmNotAdmin = computed<boolean>(() => !iAmAdmin.value);
export const iAmSuperadmin = computed<boolean>(() => auth.value?.user?.is_superadmin ?? false);
export const iAmNotSuperadmin = computed<boolean>(() => !iAmSuperadmin.value);
