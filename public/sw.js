/* global self, caches, fetch, Request, Response, URL, location */
/**
 * MTAV's service worker: the app keeps working without the server. Pages (and their data)
 * come from the network when it answers, and are kept as they were last loaded; when the
 * server cannot be reached, the kept copy answers instead, and the page is told so. The
 * app's own files (built, hashed) and pictures are kept once fetched. Nothing that changes
 * anything (a form, an upload) ever goes through here: those need the server.
 */
const PAGES = 'mtav-pages-v1';
const FILES = 'mtav-files-v1';
/** Past this, a slow answer gives way to the kept copy (when there is one) */
const PATIENCE_MS = 6000;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

/** Someone else signed in, or signed out: what was kept was theirs */
self.addEventListener('message', (event) => {
  if (event.data === 'forget') event.waitUntil(caches.delete(PAGES));
});

const tell = async (online) =>
  (await self.clients.matchAll()).forEach((client) =>
    client.postMessage({ type: 'connection', online }),
  );

/** A page and each of its partial answers are kept apart (a partial holds only some data) */
const keyOf = (request) => {
  const inertia = request.headers.get('X-Inertia');
  const partial = request.headers.get('X-Inertia-Partial-Data') ?? '';
  const url = new URL(request.url);
  url.searchParams.set('__kept', inertia ? `page:${partial}` : 'document');
  return new Request(url.toString());
};

const keptFile = /^\/(build|images|fonts)\/|\/thumbnail$/;

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== location.origin) return;

  if (keptFile.test(url.pathname)) {
    event.respondWith(fileFirst(request));
  } else if (request.mode === 'navigate' || request.headers.get('X-Inertia')) {
    event.respondWith(networkFirst(request));
  }
});

/** Hashed builds and pictures never change under the same address: kept once fetched */
async function fileFirst(request) {
  const cache = await caches.open(FILES);
  const kept = await cache.match(request);
  if (kept) return kept;
  const response = await fetch(request);
  if (response.ok) cache.put(request, response.clone());
  return response;
}

/**
 * A page opened afresh (a reload) whose document was never kept, though its data was (it
 * was moved to within the app): any kept document, carrying this page's data instead
 */
async function documentFrom(cache, request) {
  const url = new URL(request.url);
  url.searchParams.set('__kept', 'page:');
  const data = await cache.match(new Request(url.toString()), { ignoreVary: true });
  const shell = (await cache.keys()).find((key) => key.url.includes('__kept=document'));
  if (!data || !shell) return undefined;
  const document = await (await cache.match(shell)).text();
  const tag = /(<script data-page="app" type="application\/json">)([\s\S]*?)(<\/script>)/;
  const found = document.match(tag);
  if (!found) return undefined;
  // What only a whole document carries (the app's routes, its servers) comes from the kept one
  const kept = JSON.parse(found[2]);
  const page = JSON.parse(await data.text());
  page.props = { ...kept.props, ...page.props };
  const json = JSON.stringify(page).replace(/</g, '\\u003c');
  const html = document.replace(tag, (_, open, _old, close) => open + json + close);
  return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

async function networkFirst(request) {
  const cache = await caches.open(PAGES);
  const key = keyOf(request);
  const fresh = fetch(request).then((response) => {
    // A redirect is not the page (a browser refuses it in its place later)
    if (response.ok && !response.redirected) cache.put(key, response.clone());
    tell(true);
    return response;
  });

  try {
    return await Promise.race([
      fresh,
      new Promise((_, reject) => setTimeout(() => reject(new Error('slow')), PATIENCE_MS)),
    ]);
  } catch {
    const kept =
      (await cache.match(key, { ignoreVary: true })) ??
      (request.mode === 'navigate' ? await documentFrom(cache, request) : undefined);
    if (kept) {
      tell(false);
      return kept;
    }
    // Nothing kept: wait for the network after all, or fail as the network does
    try {
      return await fresh;
    } catch {
      tell(false);
      return Response.error();
    }
  }
}
