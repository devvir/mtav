import { reactive } from 'vue';

export type ToastTone = 'success' | 'info' | 'warning' | 'error';

export interface Toast {
  id: number;
  tone: ToastTone;
  title?: string;
  message: string;
  url?: string | null;
}

const toasts = reactive<Toast[]>([]);
let lastId = 0;

/**
 * Short-lived messages in a corner of the screen: flash messages after an action,
 * and notifications arriving live. Errors stay until dismissed.
 */
export function useToasts() {
  function dismiss(id: number) {
    const index = toasts.findIndex((toast) => toast.id === id);
    if (index >= 0) toasts.splice(index, 1);
  }

  function show(toast: Omit<Toast, 'id'>) {
    const id = ++lastId;
    toasts.push({ id, ...toast });

    if (toast.tone !== 'error') {
      setTimeout(() => dismiss(id), toast.title ? 8000 : 5000);
    }

    return id;
  }

  return { toasts, show, dismiss };
}
