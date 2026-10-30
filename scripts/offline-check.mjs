/* global process, console, URL -- Node runs it */
// Working offline, checked in a real browser (the browser suite cannot cut the network):
// signed in, the main places kept in the background; then offline, an unvisited place
// opens, a reload opens, a change is held with its message, and the status clears back
// online. Against a seeded app, built assets:
//
//   BASE=http://127.0.0.1:8010 node scripts/offline-check.mjs
import { createRequire } from 'module';
const require = createRequire(`${process.cwd()}/package.json`);
const { chromium } = require('playwright');
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1400, height: 900 } });
const page = await context.newPage();
page.on('pageerror', (e) => console.log('pageerror', e.message));
const B = process.env.BASE;
await page.goto(B + '/login');
await page.locator('[role=dialog] input[name=email]').waitFor({ timeout: 90000 });
await page.fill('[role=dialog] input[name=email]', 'member@example.com');
await page.fill('[role=dialog] input[name=password]', 'password');
await page.locator('[role=dialog] [type=submit]').click();
await page.waitForURL((u) => !u.pathname.startsWith('/login'));
const project = new URL(page.url()).pathname.match(/\/projects\/\d+/)?.[0] ?? '/projects/1';
await page.goto(B + project);
await page.waitForLoadState('networkidle');
console.log(
  'worker',
  await page.evaluate(async () => !!(await navigator.serviceWorker.ready).active),
);
await page.reload();
await page.waitForLoadState('networkidle');
await page.waitForTimeout(6000); // the main places kept in the background
await context.setOffline(true);
// Moving to a place never visited, then reloading the page itself
await page.click('.shell-sidebar >> text=Events');
await page.waitForTimeout(1500);
console.log(
  'events offline:',
  new URL(page.url()).pathname,
  await page.locator('h1').first().textContent(),
  'status shown:',
  await page.locator('.shell-offline').count(),
);
await page.click('.shell-sidebar >> text=Lottery');
await page.waitForTimeout(1500);
console.log('lottery offline:', await page.locator('h1').first().textContent());
await page.reload().catch((e) => console.log('reload', e.message));
await page.waitForTimeout(2500);
console.log(
  'after reload offline:',
  await page
    .locator('h1')
    .first()
    .textContent()
    .catch(() => 'nothing'),
);
// A change offline: moving a unit in the ranking
const up = page.locator('[aria-label^="Move"][aria-label$="up"]:not([disabled])').first();
if (await up.count()) {
  await up.click();
  await page.waitForTimeout(1200);
}
console.log('toast:', await page.locator('.mantine-Notification-root').allTextContents());
await context.setOffline(false);
await page.click('.shell-sidebar >> text=Home');
await page.waitForTimeout(2000);
console.log('back online, status shown:', await page.locator('.shell-offline').count());
await browser.close();
