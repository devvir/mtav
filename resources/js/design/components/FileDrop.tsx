import { ActionIcon, Collapse, Group, Stack, Text } from '@mantine/core';
import { Dropzone, type DropzoneProps, type FileRejection } from '@mantine/dropzone';
import { useReducedMotion } from '@mantine/hooks';
import { IconFile, IconFileUpload, IconX } from '@tabler/icons-react';
import { useState, type ReactNode } from 'react';

/**
 * Pick or drop files; shows what was chosen, each removable, the ones too large marked, and
 * says which were refused for their kind, and when some were left out for being too many.
 * The words are the caller's.
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
    /** A file's size, written */
    size: (bytes: number) => string;
    /** Said of a file of a kind that is not accepted */
    refused: (name: string) => string;
    /** Said when more were chosen than `maxFiles`: the first ones are kept */
    tooMany?: string;
    remove: (name: string) => string;
  };
}) {
  const [refused, setRefused] = useState<string[]>([]);
  const [leftOut, setLeftOut] = useState(false);

  // Those accepted and those refused come together: each said, neither undoing the other
  const add = (picked: File[], rejections: FileRejection[]) => {
    setRefused(rejections.map(({ file }) => file.name));
    if (!multiple) {
      setLeftOut(false);
      if (picked.length) onChange(picked.slice(0, 1));
      return;
    }
    const room = Math.max(0, (maxFiles ?? Infinity) - files.length);
    setLeftOut(picked.length > room);
    if (picked.length && room) onChange([...files, ...picked.slice(0, room)]);
  };

  const notes = [
    ...refused.map(words.refused),
    ...(leftOut && words.tooMany ? [words.tooMany] : []),
  ].join(' ');

  return (
    <Stack gap="sm">
      <Dropzone
        onDrop={() => undefined}
        onDropAny={add}
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
      <Said text={notes} />
      <Said text={error} />
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
                <Text className="ds-truncate" style={{ flex: 1 }} fw={550} title={file.name}>
                  {file.name}
                </Text>
                <Text className="ds-caption" c={tooLarge ? 'red' : undefined}>
                  {tooLarge ? words.tooLarge : words.size(file.size)}
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

/** A message that comes and goes, its words kept while it folds away */
function Said({ text }: { text: ReactNode }) {
  const still = useReducedMotion();
  const [said, setSaid] = useState<ReactNode>(text);
  if (text && text !== said) setSaid(text);

  return (
    <Collapse expanded={Boolean(text)} transitionDuration={still ? 0 : 220}>
      <Text c="red" size="sm" role="alert">
        {said}
      </Text>
    </Collapse>
  );
}
