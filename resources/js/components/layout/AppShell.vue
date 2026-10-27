<script setup lang="ts">
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import Toaster from '@/components/feedback/Toaster.vue';
import TourHost from '@/components/help/TourHost.vue';
import DialogHost from '@/components/modal/DialogHost.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLiveNotifications } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
  Bell,
  Building2,
  ChevronsUpDown,
  FolderKanban,
  LogOut,
  Menu,
  Settings,
  ShieldCheck,
  X,
} from 'lucide-vue-next';
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import type { NavItem } from './types';

/**
 * The frame of every signed-in page: top bar (project switcher, notifications, user
 * menu), the area's navigation (sidebar on large screens, drawer on small ones, plus
 * optional bottom tabs on phones), and the page.
 */
const props = defineProps<{
  /** What this area is about (the project's name, "Platform console", …) */
  heading: string;
  nav: NavItem[];
  /** Phone bottom tabs (the member app's main destinations) */
  tabs?: NavItem[];
  /** A link to the sibling area (member app ⇄ admin area) */
  switchTo?: { label: string; href: string; icon: NavItem['icon'] } | null;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user!);
const project = computed(() => page.props.project);
const unread = computed(() => page.props.notifications?.unread ?? 0);

const drawer = ref(false);
router.on('navigate', () => (drawer.value = false));

useLiveNotifications();

const navLink = (item: NavItem) =>
  cn(
    'flex min-h-11 items-center gap-3 rounded-lg px-3 text-[0.95rem] font-medium transition',
    item.active
      ? 'bg-interactive text-interactive-foreground shadow-sm'
      : 'text-text hover:bg-surface-interactive-hover',
  );
</script>

<template>
  <div class="min-h-dvh lg:grid lg:grid-cols-[16rem_1fr]">
    <!-- Sidebar (large screens) -->
    <aside
      class="sticky top-0 hidden h-dvh flex-col gap-2 border-r-2 border-border bg-sidebar p-4 lg:flex"
    >
      <Link :href="route('home')" class="mb-2 flex items-center gap-2 px-2 text-lg font-bold">
        <Building2 class="size-6 text-interactive" /> {{ page.props.name }}
      </Link>
      <p class="truncate px-3 pb-1 text-xs font-semibold tracking-wide text-text-muted uppercase">
        {{ heading }}
      </p>
      <nav class="flex flex-1 flex-col gap-1 overflow-y-auto">
        <Link v-for="item in props.nav" :key="item.href" :href="item.href" :class="navLink(item)">
          <component :is="item.icon" class="size-5 shrink-0" />
          <span class="flex-1 truncate">{{ item.label }}</span>
          <span
            v-if="item.badge"
            class="rounded-full bg-warning px-2 text-xs font-bold text-warning-foreground"
            >{{ item.badge }}</span
          >
        </Link>
      </nav>
      <Link
        v-if="switchTo"
        :href="switchTo.href"
        class="flex min-h-11 items-center gap-3 rounded-lg border-2 border-border px-3 font-medium hover:border-border-interactive"
      >
        <component :is="switchTo.icon" class="size-5" /> {{ switchTo.label }}
      </Link>
    </aside>

    <div class="flex min-h-dvh min-w-0 flex-col">
      <!-- Top bar -->
      <header
        class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b-2 border-border bg-surface/95 px-3 backdrop-blur sm:px-5"
      >
        <button
          type="button"
          class="flex size-11 items-center justify-center rounded-lg hover:bg-surface-interactive-hover lg:hidden"
          :aria-label="_('Menu')"
          @click="drawer = true"
        >
          <Menu class="size-6" />
        </button>

        <DropdownMenu v-if="project">
          <DropdownMenuTrigger
            class="flex min-h-11 min-w-0 items-center gap-2 rounded-lg px-2 font-semibold hover:bg-surface-interactive-hover"
          >
            <span class="truncate">{{ project.name }}</span>
            <ChevronsUpDown class="size-4 shrink-0 text-text-muted" />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="start" class="w-64">
            <DropdownMenuLabel>{{ _('Your projects') }}</DropdownMenuLabel>
            <DropdownMenuItem v-for="item in page.props.projects" :key="item.id" as-child>
              <Link :href="route('projects.show', item.id)" class="w-full">
                <span class="flex-1 truncate">{{ item.name }}</span>
                <ShieldCheck
                  v-if="item.is_admin"
                  class="size-4 text-text-muted"
                  :aria-label="_('Admin')"
                />
              </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
              <Link :href="route('projects.index')" class="w-full"
                ><FolderKanban class="size-4" /> {{ _('All your projects') }}</Link
              >
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
        <span v-else class="truncate px-2 font-semibold">{{ heading }}</span>

        <div class="ml-auto flex items-center gap-1">
          <Link
            :href="route('notifications.index')"
            class="relative flex size-11 items-center justify-center rounded-lg hover:bg-surface-interactive-hover"
            :aria-label="
              unread ? _('Notifications, {count} unread', { count: unread }) : _('Notifications')
            "
          >
            <Bell class="size-6" />
            <span
              v-if="unread"
              class="absolute top-1.5 right-1.5 flex min-w-5 items-center justify-center rounded-full bg-error px-1 text-[0.7rem] font-bold text-error-foreground"
            >
              {{ unread > 99 ? '99+' : unread }}
            </span>
          </Link>

          <DropdownMenu>
            <DropdownMenuTrigger
              class="flex size-11 items-center justify-center rounded-full"
              :aria-label="_('Your account')"
            >
              <PersonAvatar :name="user.name" :src="user.avatar" size="sm" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-64">
              <DropdownMenuLabel>
                <span class="block truncate">{{ user.name }}</span>
                <span class="block truncate text-xs font-normal text-text-muted">{{
                  user.email
                }}</span>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem as-child>
                <Link :href="route('projects.index')" class="w-full"
                  ><FolderKanban class="size-4" /> {{ _('Your projects') }}</Link
                >
              </DropdownMenuItem>
              <DropdownMenuItem as-child>
                <Link :href="route('profile.edit')" class="w-full"
                  ><Settings class="size-4" /> {{ _('Settings') }}</Link
                >
              </DropdownMenuItem>
              <DropdownMenuItem v-if="user.is_superadmin" as-child>
                <Link :href="route('admin.projects.index')" class="w-full"
                  ><ShieldCheck class="size-4" /> {{ _('Platform console') }}</Link
                >
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem as-child>
                <Link :href="route('logout')" method="post" as="button" class="w-full">
                  <LogOut class="size-4" /> {{ _('Sign out') }}
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <slot name="banner" />

      <main
        :class="
          cn(
            'mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 lg:py-8',
            tabs?.length && 'pb-28 lg:pb-8',
          )
        "
      >
        <slot />
      </main>

      <!-- Bottom tabs (phones) -->
      <nav
        v-if="tabs?.length"
        class="fixed inset-x-0 bottom-0 z-30 grid border-t-2 border-border bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden"
        :style="{ gridTemplateColumns: `repeat(${tabs.length}, minmax(0, 1fr))` }"
        :aria-label="_('Main')"
      >
        <Link
          v-for="item in tabs"
          :key="item.href"
          :href="item.href"
          :class="
            cn(
              'flex min-h-16 flex-col items-center justify-center gap-1 text-xs font-medium',
              item.active ? 'text-interactive' : 'text-text-muted',
            )
          "
          :aria-current="item.active ? 'page' : undefined"
        >
          <component :is="item.icon" class="size-6" />
          {{ item.label }}
        </Link>
      </nav>
    </div>

    <!-- Drawer (small screens) -->
    <DialogRoot v-model:open="drawer">
      <DialogPortal>
        <DialogOverlay
          class="fixed inset-0 z-40 bg-black/40 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
        />
        <DialogContent
          class="fixed inset-y-0 left-0 z-50 flex w-[min(20rem,85vw)] flex-col gap-2 bg-sidebar p-4 shadow-xl duration-200 data-[state=closed]:animate-out data-[state=closed]:slide-out-to-left data-[state=open]:animate-in data-[state=open]:slide-in-from-left"
        >
          <div class="flex items-center justify-between">
            <DialogTitle class="truncate px-2 font-semibold">{{ heading }}</DialogTitle>
            <button
              type="button"
              class="flex size-11 items-center justify-center rounded-lg"
              :aria-label="_('Close')"
              @click="drawer = false"
            >
              <X class="size-6" />
            </button>
          </div>
          <nav class="flex flex-1 flex-col gap-1 overflow-y-auto">
            <Link
              v-for="item in props.nav"
              :key="item.href"
              :href="item.href"
              :class="navLink(item)"
            >
              <component :is="item.icon" class="size-5 shrink-0" />
              <span class="flex-1">{{ item.label }}</span>
              <span
                v-if="item.badge"
                class="rounded-full bg-warning px-2 text-xs font-bold text-warning-foreground"
                >{{ item.badge }}</span
              >
            </Link>
          </nav>
          <Link
            v-if="switchTo"
            :href="switchTo.href"
            class="flex min-h-11 items-center gap-3 rounded-lg border-2 border-border px-3 font-medium"
          >
            <component :is="switchTo.icon" class="size-5" /> {{ switchTo.label }}
          </Link>
        </DialogContent>
      </DialogPortal>
    </DialogRoot>

    <Toaster />
    <DialogHost />
    <TourHost />
  </div>
</template>
