import { ActionIcon, Group, Stack, Text } from '@mantine/core';
import { Dropzone, type DropzoneProps } from '@mantine/dropzone';
import { IconFile, IconFileUpload, IconX } from '@tabler/icons-react';
import { useState, type ReactNode } from 'react';

/** A size people read: KB under a megabyte, MB above */
export const fileSize = (bytes: number) =>
  bytes > 1024 * 1024
    ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;

/**
 * Pick or drop files; shows what was chosen, each removable, the ones too large marked, and
 * says which were refused for their kind. The words are the caller's.
 */
export function FileDrop({
  files,
  onChange,
  accept,
  multiple,
  maxFiles,
  maxFileBytes,
  error,
  words,
}: {
  files: File[];
  onChange: (files: File[]) => void;
  accept?: DropzoneProps['accept'];
  multiple?: boolean;
  maxFiles?: number;
  maxFileBytes?: number;
  error?: ReactNode;
  words: {
    prompt: ReactNode;
    limit?: ReactNode;
    tooLarge: ReactNode;
    /** Said of a file of a kind that is not accepted */
    refused: (name: string) => string;
    remove: (name: string) => string;
  };
}) {
  const [refused, setRefused] = useState<string[]>([]);

  const add = (picked: File[]) => {
    setRefused([]);
    onChange(multiple ? [...files, ...picked].slice(0, maxFiles ?? Infinity) : picked.slice(0, 1));
  };

  return (
    <Stack gap="sm">
      <Dropzone
        onDrop={add}
        onReject={(rejections) => setRefused(rejections.map(({ file }) => file.name))}
        accept={accept}
        multiple={multiple}
        className="ds-drop"
        data-error={error ? true : undefined}
        radius="lg"
      >
        <Stack align="center" gap={6} py="lg" style={{ pointerEvents: 'none' }}>
          <IconFileUpload size={34} stroke={1.6} className="ds-drop-icon" />
          <Text fw={600}>{words.prompt}</Text>
          {words.limit ? <Text className="ds-caption">{words.limit}</Text> : null}
        </Stack>
      </Dropzone>
      {refused.length ? (
        <Text c="red" size="sm" role="alert">
          {refused.map(words.refused).join(' ')}
        </Text>
      ) : null}
      {error ? (
        <Text c="red" size="sm" role="alert">
          {error}
        </Text>
      ) : null}
      {files.length ? (
        <Stack gap={6} component="ul" style={{ listStyle: 'none', padding: 0, margin: 0 }}>
          {files.map((file, index) => {
            const tooLarge = maxFileBytes !== undefined && file.size > maxFileBytes;
            return (
              <Group
                key={`${file.name}-${index}`}
                component="li"
                className="ds-file"
                gap="sm"
                wrap="nowrap"
              >
                <IconFile size={20} stroke={1.6} />
                <Text className="ds-truncate" style={{ flex: 1 }} fw={550}>
                  {file.name}
                </Text>
                <Text className="ds-caption" c={tooLarge ? 'red' : undefined}>
                  {tooLarge ? words.tooLarge : fileSize(file.size)}
                </Text>
                <ActionIcon
                  aria-label={words.remove(file.name)}
                  onClick={() => onChange(files.filter((_, at) => at !== index))}
                >
                  <IconX size={18} />
                </ActionIcon>
              </Group>
            );
          })}
        </Stack>
      ) : null}
    </Stack>
  );
}
