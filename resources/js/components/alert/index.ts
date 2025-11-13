import { cva, type VariantProps } from 'class-variance-authority';

export { default as Alert } from './Alert.vue';
export { default as AlertDescription } from './AlertDescription.vue';
export { default as AlertTitle } from './AlertTitle.vue';

export const alertVariants = cva(
  'relative w-full rounded-lg border p-4 [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground border-border [&>svg]:text-foreground',
        success:
          'border-green-500/50 bg-green-50 dark:bg-green-950/30 text-green-900 dark:text-green-100 [&>svg]:text-green-600 dark:[&>svg]:text-green-400',
        info:
          'border-blue-500/50 bg-blue-50 dark:bg-blue-950/30 text-blue-900 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400',
        warning:
          'border-yellow-500/50 bg-yellow-50 dark:bg-yellow-950/30 text-yellow-900 dark:text-yellow-100 [&>svg]:text-yellow-600 dark:[&>svg]:text-yellow-400',
        error:
          'border-red-500/50 bg-red-50 dark:bg-red-950/30 text-red-900 dark:text-red-100 [&>svg]:text-red-600 dark:[&>svg]:text-red-400',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

export type AlertVariant = VariantProps<typeof alertVariants>['variant'];
