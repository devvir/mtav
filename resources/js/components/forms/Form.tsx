import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { Button, Group, Stack, Text } from '@mantine/core';
import type { FormEvent, ReactNode } from 'react';

/**
 * A form: validated by the server only (every error reads the same, in the app's
 * language), submitted with Enter or its button.
 */
export function Form({
  onSubmit,
  children,
  gap = 'lg',
  ...rest
}: { onSubmit: () => void; children: ReactNode; gap?: string } & Omit<
  React.FormHTMLAttributes<HTMLFormElement>,
  'onSubmit'
>) {
  return (
    <form
      noValidate
      onSubmit={(event: FormEvent) => {
        event.preventDefault();
        onSubmit();
      }}
      {...rest}
    >
      <Stack gap={gap}>{children}</Stack>
    </form>
  );
}

/** What the asterisk means, in the row of a form's buttons */
export function RequiredNote() {
  return <Text className="ds-caption form-note">{_('Fields marked with * are required.')}</Text>;
}

/**
 * A form's end: what the asterisk means (unless no field is required), going back without
 * saving, and its main action.
 */
export function FormActions({
  submit,
  processing,
  cancelHref,
  requiredNote = true,
  children,
}: {
  submit: ReactNode;
  processing?: boolean;
  cancelHref?: string;
  requiredNote?: boolean;
  children?: ReactNode;
}) {
  return (
    <Group justify="flex-end" gap="sm" pt="md" className="form-actions">
      {requiredNote ? <RequiredNote /> : null}
      {children}
      {cancelHref ? (
        <Button variant="default" component={Link} href={cancelHref}>
          {_('Cancel')}
        </Button>
      ) : null}
      <Button type="submit" loading={processing}>
        {submit}
      </Button>
    </Group>
  );
}
