// Copilot - Pending review
import { usePage } from '@inertiajs/vue3';

/**
 * Composable that maintains a reactive, mutable copy of Inertia props.
 * Useful for optimistic updates without requiring a full page reload.
 *
 * The returned object maintains the same structure as usePage().props,
 * but is a reactive copy that can be mutated to update UI optimistically.
 * Nested mutations persist until the next Inertia update.
 *
 * @returns Reactive copy of Inertia props
 */
export function useGlobalProps() {
  const page = usePage();
  const globalProps = reactive<Record<string, any>>({});

  // Initialize with current props immediately
  Object.assign(globalProps, page.props);

  // Sync whenever props change
  watchEffect(() => {
    // Remove stale props that no longer exist in the current Inertia props
    for (const key in globalProps) {
      if (!(key in page.props)) {
        delete globalProps[key];
      }
    }
    // Update with current props (adds new ones, updates existing)
    Object.assign(globalProps, page.props);
  });

  return globalProps;
}
