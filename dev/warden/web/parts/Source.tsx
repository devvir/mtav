import { Alert, Skeleton } from '@mantine/core';
import type { ReactNode } from 'react';
import type { Reading } from '../api';

/** A source's reading: its content once read, a placeholder until then, and its problem if it could not */
export function Source<T>({
  reading,
  height = 80,
  children,
}: {
  reading?: Reading<T>;
  height?: number;
  children: (data: T) => ReactNode;
}) {
  if (reading?.error && !reading.data) {
    return (
      <Alert color="red" variant="light">
        {reading.error}
      </Alert>
    );
  }
  if (!reading?.data) return <Skeleton h={height} radius="md" />;
  return <>{children(reading.data)}</>;
}
