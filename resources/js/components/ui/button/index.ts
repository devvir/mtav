import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'

export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:ring-offset-2 focus-visible:ring-offset-focus-ring-offset aria-invalid:ring-2 aria-invalid:ring-error aria-invalid:border-error',
  {
    variants: {
      variant: {
        default:
          'bg-interactive text-interactive-foreground shadow-sm hover:bg-interactive-hover active:bg-interactive-active min-h-[44px] @md:min-h-[36px]',
        destructive:
          'bg-error text-error-foreground shadow-sm hover:opacity-90 active:opacity-80 min-h-[44px] @md:min-h-[36px]',
        outline:
          'border-2 border-border bg-surface text-text shadow-sm hover:bg-surface-interactive-hover hover:border-border-interactive min-h-[44px] @md:min-h-[36px]',
        secondary:
          'bg-interactive-secondary text-interactive-secondary-foreground shadow-sm hover:bg-interactive-secondary-hover min-h-[44px] @md:min-h-[36px]',
        ghost:
          'hover:bg-surface-interactive-hover text-text min-h-[44px] @md:min-h-[36px]',
        link: 'text-text-link underline-offset-4 hover:text-text-link-hover hover:underline',
      },
      size: {
        default: 'h-11 px-4 py-2 has-[>svg]:px-3 @md:h-9',
        sm: 'h-10 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5 @md:h-8',
        lg: 'h-12 rounded-md px-6 has-[>svg]:px-4 @md:h-10',
        icon: 'size-11 @md:size-9',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
