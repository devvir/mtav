import { unshownErrors } from '@/lib/forms';
import { _ } from '@/lib/translations';
import { Link } from '@inertiajs/react';
import { Button, Collapse, Group, Stack, Text } from '@mantine/core';
import { useState, type FormEvent, type ReactNode } from 'react';

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

/**
 * What the server refused that no field of the form shows (`shown`: whether the form has a
 * field for it), said under the form; the last words stay as they fold away
 */
export function OtherErrors({
  errors,
  shown,
}: {
  errors: Record<string, string | undefined>;
  shown: (field: string) => boolean;
}) {
  const messages = unshownErrors(errors, shown);
  const [said, setSaid] = useState(messages);
  if (messages.length && messages.join('\n') !== said.join('\n')) setSaid(messages);

  return (
    <Collapse expanded={messages.length > 0} transitionDuration={220}>
      <Stack gap={4} role="alert" data-form-errors>
        {said.map((message) => (
          <Text key={message} size="sm" c="var(--mantine-color-error)">
            {message}
          </Text>
        ))}
      </Stack>
    </Collapse>
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
  disabled,
  cancelHref,
  requiredNote = true,
  children,
}: {
  submit: ReactNode;
  processing?: boolean;
  /** Not to be sent as it is (the form says why) */
  disabled?: boolean;
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
      <Button type="submit" loading={processing} disabled={disabled}>
        {submit}
      </Button>
    </Group>
  );
}
