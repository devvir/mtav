import { languageTag } from '@/lib/translations';

let name = 'MTAV';

/** The deployment's name, as the server gives it (white-labelled, another), set on start */
export const setAppName = (given: string | undefined) => {
  if (given) name = given;
};

/** The deployment's name, as people read it ("the MTAV team") */
export const appName = (): string => name;

/** A number as the app's language writes it, with at most so many decimals */
export const decimal = (value: number, digits: number): string =>
  value.toLocaleString(languageTag(), { maximumFractionDigits: digits });

/** A size in megabytes, as people read it: gigabytes from one on */
export const megabytes = (mb: number): string =>
  mb >= 1024 ? `${decimal(mb / 1024, 1)} GB` : `${decimal(mb, 1)} MB`;

/** A size in bytes, as people read it: "820 KB", "3.4 MB" */
export const fileSize = (bytes: number): string =>
  bytes >= 1024 * 1024
    ? `${decimal(bytes / 1024 / 1024, 1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;
