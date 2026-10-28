import { createStore } from './store';

export interface ConfirmOptions {
  title: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  /** Red confirm button, for actions that remove or undo things */
  danger?: boolean;
  /** Ask the person to type this text to confirm (a project's name) */
  typeToConfirm?: string;
}

export interface PromptOptions extends Omit<ConfirmOptions, 'typeToConfirm'> {
  label: string;
  placeholder?: string;
  multiline?: boolean;
  /** Minimum length of the answer */
  minLength?: number;
}

export type DialogRequest = { id: number } & (
  | { kind: 'confirm'; options: ConfirmOptions; resolve: (value: boolean) => void }
  | { kind: 'prompt'; options: PromptOptions; resolve: (value: string | null) => void }
);

let lastId = 0;

/** The questions waiting, the first one shown (by DialogHost, in the shell) */
export const dialogQueue = createStore<DialogRequest[]>([]);

/**
 * Ask the person something from code, and wait for the answer:
 *
 *   if (await confirm({ title: _('Remove this family?'), danger: true })) …
 *   const reason = await prompt({ title: …, label: _('Reason'), minLength: 10 });
 */
export const confirm = (options: ConfirmOptions) =>
  new Promise<boolean>((resolve) =>
    dialogQueue.set((queue) => [...queue, { id: ++lastId, kind: 'confirm', options, resolve }]),
  );

export const prompt = (options: PromptOptions) =>
  new Promise<string | null>((resolve) =>
    dialogQueue.set((queue) => [...queue, { id: ++lastId, kind: 'prompt', options, resolve }]),
  );

/** Answer the question shown, and show the next one */
export function settle(value: boolean | string | null) {
  const [current, ...rest] = dialogQueue.get();
  dialogQueue.set(rest);
  current?.resolve(value as never);
}
