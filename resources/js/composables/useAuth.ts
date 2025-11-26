import type { Auth, AuthUser } from '@/types/inertia';

const page = usePage();

export const auth = computed<Auth>(() => page.props.auth);
export const currentUser = computed<Admin | Member>(() =>
  iAmAdmin ? (auth.value.user as AuthUser & Admin) : (auth.value.user as AuthUser & Member),
);

export const iAmMember = computed<boolean>(() => auth.value.user?.is_admin === false);
export const iAmAdmin = computed<boolean>(() => auth.value.user?.is_admin ?? false);
export const iAmNotAdmin = computed<boolean>(() => !iAmAdmin.value);
export const iAmSuperadmin = computed<boolean>(() => auth.value.user?.is_superadmin ?? false);
export const iAmNotSuperadmin = computed<boolean>(() => !iAmSuperadmin.value);

export const can = reactive({
  create: (resource: AppEntityNS): boolean => currentUser.value?.can.create[resource] ?? false,
  viewAny: (resource: AppEntityNS): boolean => currentUser.value?.can.viewAny[resource] ?? false,
});
