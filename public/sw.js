/* global self, caches, fetch, Request, Response, URL, location */
/**
 * MTAV's service worker: the app keeps working without the server. Pages (and their data)
 * come from the network when it answers, and are kept as they were last loaded; when the
 * server cannot be reached, the kept copy answers instead, and the page is told so. The
 * app's own files (built, hashed) are kept once fetched, until a build no longer has them;
 * the project's pictures are asked of the server each time, kept for when it cannot be
 * reached. Nothing that changes anything (a form, an upload) ever goes through here: those
 * need the server.
 */
const PAGES = 'mtav-pages-v1';
const FILES = 'mtav-files-v1';
/** The person's, like their pages: forgotten with them */
const PICTURES = 'mtav-pictures-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) =>
  event.waitUntil(Promise.all([self.clients.claim(), dropPicturesKeptAsFiles()])),
);

/** Pictures an earlier worker kept among the app's files, never asked of the server again: dropped */
async function dropPicturesKeptAsFiles() {
  const cache = await caches.open(FILES);
  const pictures = (await cache.keys()).filter((key) => picture.test(new URL(key.url).pathname));
  await Promise.all(pictures.map((key) => cache.delete(key)));
}

/** Someone else signed in, or signed out: what was kept was theirs */
self.addEventListener('message', (event) => {
  if (event.data === 'forget')
    event.waitUntil(Promise.all([caches.delete(PAGES), caches.delete(PICTURES)]));
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

const keptFile = /^\/(build|images|fonts)\//;
/** Only the project's people may see them: the server is asked each time (lost access, a photo removed) */
const picture = /\/thumbnail$/;

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== location.origin) return;

  if (keptFile.test(url.pathname)) {
    event.respondWith(fileFirst(request, event));
  } else if (picture.test(url.pathname)) {
    event.respondWith(askedPicture(request));
  } else if (request.mode === 'navigate' || request.headers.get('X-Inertia')) {
    event.respondWith(networkFirst(request));
  }
});

/** Hashed builds never change under the same address: kept once fetched */
async function fileFirst(request, event) {
  const cache = await caches.open(FILES);
  const kept = await cache.match(request);
  if (kept) return kept;
  const response = await fetch(request);
  if (response.ok) {
    cache.put(request, response.clone());
    // A file never kept: maybe a new build, whose files replace the last one's
    if (new URL(request.url).pathname.startsWith('/build/')) event.waitUntil(pruneSoon());
  }
  return response;
}

let pruning = null;
const pruneSoon = () => {
  pruning ??= pruneBuilds()
    .catch(() => undefined)
    .finally(() => {
      pruning = null;
    });
  return pruning;
};

/** The files of builds gone by, which nothing asks for again: dropped */
async function pruneBuilds() {
  const response = await fetch('/build/manifest.json', { cache: 'no-store' });
  if (!response.ok) return;
  const current = new Set();
  Object.values(await response.json()).forEach((entry) =>
    [entry.file, ...(entry.css ?? []), ...(entry.assets ?? [])].forEach((file) =>
      current.add(`/build/${file}`),
    ),
  );
  if (!current.size) return;
  const cache = await caches.open(FILES);
  const gone = (await cache.keys()).filter((key) => {
    const path = new URL(key.url).pathname;
    return path.startsWith('/build/') && !current.has(path);
  });
  await Promise.all(gone.map((key) => cache.delete(key)));
}

/**
 * A picture as the server gives it now, or refuses it (and it is forgotten); the kept one
 * only when the server cannot be reached
 */
async function askedPicture(request) {
  const cache = await caches.open(PICTURES);
  try {
    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    else if (response.status !== 304) await cache.delete(request);
    return response;
  } catch (error) {
    if (request.signal?.aborted || error?.name === 'AbortError') return Response.error();
    return (await cache.match(request)) ?? Response.error();
  }
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

/**
 * The server's answer whenever it gives one, however long it takes: slow is not offline. Only
 * when it cannot be reached does the kept copy answer, and the page is told.
 */
async function networkFirst(request) {
  const cache = await caches.open(PAGES);
  const key = keyOf(request);
  try {
    const response = await fetch(request);
    // A redirect is not the page (a browser refuses it in its place later)
    if (response.ok && !response.redirected) cache.put(key, response.clone());
    tell(true);
    return response;
  } catch (error) {
    // Called off by the page (a visit replacing another, a refresh superseded): no answer was
    // wanted, and the server was never found missing
    if (request.signal?.aborted || error?.name === 'AbortError') return Response.error();
    tell(false);
    const kept =
      (await cache.match(key, { ignoreVary: true })) ??
      (request.mode === 'navigate' ? await documentFrom(cache, request) : undefined);
    return kept ?? Response.error();
  }
}

/**
 * A notification pushed by the server (lib/push.ts subscribed this browser): shown as the
 * system's own, in the person's language (the server wrote it so). Clicking it opens what
 * it is about, in a window of the app already open if there is one.
 */
self.addEventListener('push', (event) => {
  const data = event.data?.json() ?? {};
  event.waitUntil(
    self.registration.showNotification(data.title ?? 'MTAV', {
      body: data.message ?? '',
      icon: '/images/app/icon-192.png',
      badge: '/images/app/icon-192.png',
      tag: data.tag,
      data: { url: data.url ?? '/' },
    }),
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = new URL(event.notification.data?.url ?? '/', location.origin).href;
  event.waitUntil(
    (async () => {
      const open = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
      const app = open.find((client) => client.url.startsWith(location.origin));
      if (app) {
        await app.focus();
        return app.navigate(url);
      }
      return self.clients.openWindow(url);
    })(),
  );
});
