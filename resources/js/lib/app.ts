let name = 'MTAV';

/** The deployment's name, as the server gives it (white-labelled, another), set on start */
export const setAppName = (given: string | undefined) => {
  if (given) name = given;
};

/** The deployment's name, as people read it ("the MTAV team") */
export const appName = (): string => name;

/** A size in megabytes, as people read it: gigabytes from one on */
export const megabytes = (mb: number): string =>
  mb >= 1024 ? `${Math.round((mb / 1024) * 10) / 10} GB` : `${mb} MB`;
