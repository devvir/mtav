import type { AuthUser } from '@/types/inertia';

const page = usePage();

export const auth = computed(() => page.props.auth);
export const currentUser = computed(() =>
  iAmAdmin ? (auth.value.user as AuthUser & Admin) : (auth.value.user as AuthUser & Member),
);

export const iAmAdmin = computed(() => auth.value.user?.is_admin ?? false);
export const iAmNotAdmin = computed(() => !iAmAdmin.value);
export const iAmSuperadmin = computed(() => auth.value.user?.is_superadmin ?? false);
export const iAmNotSuperadmin = computed(() => !iAmSuperadmin.value);

export const can = reactive({
  create: (resource: AppEntityNS): boolean => currentUser.value?.can.create[resource] ?? false,
  viewAny: (resource: AppEntityNS): boolean => currentUser.value?.can.viewAny[resource] ?? false,
});
