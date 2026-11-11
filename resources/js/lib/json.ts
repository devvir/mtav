import { router } from '@inertiajs/react';
import { socketHeader } from './socketHeader';

/** The session's anti-forgery token, as Laravel puts it in a cookie */
const xsrfToken = () =>
  decodeURIComponent(document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

/** What every JSON request says of itself: it wants JSON, it is the page's own, and its connection */
const asked = () => ({
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
  ...socketHeader(),
});

/** No connection, answered as a server that cannot (503) */
const unreachable = () => new Response(null, { status: 503 });

/**
 * What the server has, answered in JSON, the page staying as it is (for parts of a page that
 * ask on their own, like the chat). It never rejects: no connection is answered as a server
 * that cannot (503), for each caller to take as any other failure.
 */
export const getJson = (url: string) =>
  fetch(url, { credentials: 'same-origin', headers: asked() }).catch(unreachable);

/**
 * A request answered in JSON, the page staying as it is (for work that must not reload
 * the page, like the plan editor's). `keepalive`, it is sent even as the page closes, and
 * any redirect it answers with is not followed: nobody is left to see where it leads. Once
 * it changed something, no page loaded ahead of time is shown as it was. It never rejects,
 * as `getJson`.
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
    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrfToken(), ...asked() },
    body: JSON.stringify(body),
  })
    .catch(unreachable)
    .then((response) => {
      // Pages loaded ahead of time came before the change: the next visit asks again
      if (response.ok) router.flushAll();
      return response;
    });

/** What a refused form says, field by field (Laravel's 422), first message each */
export const fieldErrors = async (response: Response): Promise<Record<string, string>> => {
  const { errors } = (await response.json()) as { errors?: Record<string, string[]> };
  return Object.fromEntries(Object.entries(errors ?? {}).map(([key, [first]]) => [key, first!]));
};
