// Copilot - Pending review
import { router } from '@inertiajs/vue3';

export const usePreferences = (preferences: Ref<ApiResource<Unit>[]>) => {
  // Reactive copy for optimistic updates
  const localPreferences = ref<ApiResource<Unit>[]>([...preferences.value]);
  const form = useForm({ preferences: localPreferences.value });
  const disabled = ref(false);

  // Track latest request to handle out-of-order responses
  let requestCounter = 0;

  // Watch for external prop changes (e.g., after reload)
  watch(
    preferences,
    (newPreferences: ApiResource<Unit>[]) => {
      localPreferences.value = [...newPreferences];
    },
    { deep: true },
  );

  const submit = (updatedPreferences: ApiResource<Unit>[]) => {
    // Optimistically update local state immediately
    localPreferences.value = [...updatedPreferences];

    // Increment request counter to track this request
    const currentRequest = ++requestCounter;

    form.preferences = updatedPreferences;
    form.post(route('lottery.preferences'), {
      preserveScroll: true,
      onError: () => {
        // Only process if this is still the latest request
        if (currentRequest === requestCounter) {
          // Disable editing on failure
          disabled.value = true;

          // Do a partial reload to get current persisted state
          router.reload({
            only: ['preferences'],
            onSuccess: () => {
              // Re-enable after reload completes
              disabled.value = false;
            },
          });
        }
      },
    });
  };

  return {
    localPreferences,
    disabled,
    submit,
  };
}
