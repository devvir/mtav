import { reactive } from 'vue';

export interface ConfirmOptions {
  title: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  /** Red confirm button, for actions that remove or undo things */
  danger?: boolean;
  /** Ask the user to type this text to confirm (e.g. a project's name) */
  typeToConfirm?: string;
}

export interface PromptOptions extends Omit<ConfirmOptions, 'typeToConfirm'> {
  label: string;
  placeholder?: string;
  multiline?: boolean;
  /** Minimum length of the answer */
  minLength?: number;
}

type Request =
  | { kind: 'confirm'; options: ConfirmOptions; resolve: (value: boolean) => void }
  | { kind: 'prompt'; options: PromptOptions; resolve: (value: string | null) => void };

const queue = reactive<Request[]>([]);

/**
 * Ask the user something from code, and wait for the answer:
 *
 *   if (await confirm({ title: _('Remove this family?'), danger: true })) …
 *   const reason = await prompt({ title: …, label: _('Reason'), minLength: 10 });
 *
 * The dialogs render in <DialogHost>, mounted once by the layouts.
 */
export function confirm(options: ConfirmOptions): Promise<boolean> {
  return new Promise((resolve) => queue.push({ kind: 'confirm', options, resolve }));
}

export function prompt(options: PromptOptions): Promise<string | null> {
  return new Promise((resolve) => queue.push({ kind: 'prompt', options, resolve }));
}

export function useDialogQueue() {
  return {
    current: () => queue[0] ?? null,
    settle(value: boolean | string | null) {
      const request = queue.shift();
      request?.resolve(value as never);
    },
  };
}
