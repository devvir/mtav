import type { InertiaFormProps } from '@inertiajs/react';
import type { ChangeEvent } from 'react';

type Data = Record<string, unknown>;

/**
 * A form field's props for a Mantine input: its name (what tests and the browser find it
 * by), its value, how it changes, and the server's error for it.
 *
 *   <TextInput label={_('Email')} {...field(form, 'email')} />
 */
export function field<T extends Data>(form: InertiaFormProps<T>, name: keyof T & string) {
  return {
    name,
    value: (form.data[name] ?? '') as string,
    onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
      form.setData(name as never, event.currentTarget.value as never),
    error: (form.errors as Record<string, string | undefined>)[name],
  };
}

/** The same, for a checkbox or a switch */
export function checkbox<T extends Data>(form: InertiaFormProps<T>, name: keyof T & string) {
  return {
    name,
    checked: Boolean(form.data[name]),
    onChange: (event: ChangeEvent<HTMLInputElement>) =>
      form.setData(name as never, event.currentTarget.checked as never),
    error: (form.errors as Record<string, string | undefined>)[name],
  };
}

/** The same, for a select (Mantine gives the chosen value, or null) */
export function select<T extends Data>(form: InertiaFormProps<T>, name: keyof T & string) {
  return {
    name,
    value: form.data[name] == null ? null : String(form.data[name]),
    onChange: (value: string | null) => form.setData(name as never, value as never),
    error: (form.errors as Record<string, string | undefined>)[name],
  };
}

/** The same, for a native select (Mantine's NativeSelect: a real <select>, as phones show it) */
export function nativeSelect<T extends Data>(form: InertiaFormProps<T>, name: keyof T & string) {
  return {
    name,
    value: form.data[name] == null ? '' : String(form.data[name]),
    onChange: (event: ChangeEvent<HTMLSelectElement>) =>
      form.setData(name as never, event.currentTarget.value as never),
    error: (form.errors as Record<string, string | undefined>)[name],
  };
}
