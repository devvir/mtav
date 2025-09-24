import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';
import { HTMLAttributes } from 'vue';

export interface SidebarProps {
  side?: 'left' | 'right';
  variant?: 'sidebar' | 'floating' | 'inset';
  collapsible?: 'offcanvas' | 'icon' | 'none';
  class?: HTMLAttributes['class'];
}

export { default as Sidebar } from '@/components/layout/sidebar/Sidebar.vue';
export { default as SidebarContent } from '@/components/layout/sidebar/SidebarContent.vue';
export { default as SidebarFooter } from '@/components/layout/sidebar/SidebarFooter.vue';
export { default as SidebarGroup } from '@/components/layout/sidebar/SidebarGroup.vue';
export { default as SidebarGroupAction } from '@/components/layout/sidebar/SidebarGroupAction.vue';
export { default as SidebarGroupContent } from '@/components/layout/sidebar/SidebarGroupContent.vue';
export { default as SidebarGroupLabel } from '@/components/layout/sidebar/SidebarGroupLabel.vue';
export { default as SidebarHeader } from '@/components/layout/sidebar/SidebarHeader.vue';
export { default as SidebarInset } from '@/components/layout/sidebar/SidebarInset.vue';
export { default as SidebarMenu } from '@/components/layout/sidebar/SidebarMenu.vue';
export { default as SidebarMenuButton } from '@/components/layout/sidebar/SidebarMenuButton.vue';
export { default as SidebarMenuItem } from '@/components/layout/sidebar/SidebarMenuItem.vue';
export { default as SidebarProvider } from '@/components/layout/sidebar/SidebarProvider.vue';
export { default as SidebarTrigger } from '@/components/layout/sidebar/SidebarTrigger.vue';

export { default as NavFooter } from '@/components/layout/NavFooter.vue';
export { default as NavMain } from '@/components/layout/NavMain.vue';
export { default as NavUser } from '@/components/layout/NavUser.vue';
export { default as UserMenuContent } from '@/components/layout/UserMenuContent.vue';

export { useSidebar } from './utils';

export const sidebarMenuButtonVariants = cva(
  'peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-hidden ring-sidebar-ring transition-[width,height,padding] hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 active:bg-accent active:text-accent-foreground disabled:pointer-events-none disabled:opacity-50 group-has-data-[sidebar=menu-action]/menu-item:pr-8 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-accent data-[active=true]:font-medium data-[active=true]:text-accent-foreground data-[state=open]:hover:bg-accent data-[state=open]:hover:text-accent-foreground group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:pr-2! [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0',
  {
    variants: {
      variant: {
        default: 'hover:bg-accent hover:text-accent-foreground',
        outline:
          'bg-background shadow-[0_0_0_1px_hsl(var(--sidebar-border))] hover:bg-accent hover:text-accent-foreground hover:shadow-[0_0_0_1px_hsl(var(--accent))]',
      },
      size: {
        default: 'h-8 text-sm',
        sm: 'h-7 text-xs',
        lg: 'h-12 text-sm group-data-[collapsible=icon]:p-0!',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

export type SidebarMenuButtonVariants = VariantProps<typeof sidebarMenuButtonVariants>;
