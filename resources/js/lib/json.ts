/** The session's anti-forgery token, as Laravel puts it in a cookie */
const xsrfToken = () =>
  decodeURIComponent(document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

/**
 * A request answered in JSON, the page staying as it is (for work that must not reload
 * the page, like the plan editor's)
 */
export const sendJson = (method: 'POST' | 'PUT', url: string, body: unknown) =>
  fetch(url, {
    method,
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrfToken(),
    },
    body: JSON.stringify(body),
  });

/** What a refused form says, field by field (Laravel's 422), first message each */
export const fieldErrors = async (response: Response): Promise<Record<string, string>> => {
  const { errors } = (await response.json()) as { errors?: Record<string, string[]> };
  return Object.fromEntries(Object.entries(errors ?? {}).map(([key, [first]]) => [key, first!]));
};
