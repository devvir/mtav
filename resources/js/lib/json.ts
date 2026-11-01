/** The session's anti-forgery token, as Laravel puts it in a cookie */
const xsrfToken = () =>
  decodeURIComponent(document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

/**
 * A request answered in JSON, the page staying as it is (for work that must not reload
 * the page, like the plan editor's). `keepalive`, it is sent even as the page closes, and
 * any redirect it answers with is not followed: nobody is left to see where it leads. It
 * never rejects: no connection is answered as a server that cannot (503), for each caller to
 * take as any other failure.
 */
export const sendJson = (
  method: 'POST' | 'PUT' | 'DELETE',
  url: string,
  body: unknown,
  { keepalive = false } = {},
) =>
  fetch(url, {
    method,
    keepalive,
    redirect: keepalive ? 'manual' : 'follow',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrfToken(),
    },
    body: JSON.stringify(body),
  }).catch(() => new Response(null, { status: 503 }));

/** What a refused form says, field by field (Laravel's 422), first message each */
export const fieldErrors = async (response: Response): Promise<Record<string, string>> => {
  const { errors } = (await response.json()) as { errors?: Record<string, string[]> };
  return Object.fromEntries(Object.entries(errors ?? {}).map(([key, [first]]) => [key, first!]));
};
