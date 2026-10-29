/* global process, console, document, window -- Node runs it; the marks run in the page */
// The pictures of the illustrated manuals (resources/views/guides/<locale>/guide.<role>.html):
// the real app, seeded with the demo data, each picture cut to what matters and marked
// (where to click, numbered). Run against a seeded app, once per language, the demo
// accounts set to that language beforehand (see README.md):
//
//   BASE=http://127.0.0.1:8010 LOCALE=es_UY PASSWORD=password node scripts/guide-shots/shots.mjs
//
// Pictures land in public/images/guide/<locale>/<role>-<name>.jpg.
import { mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(`${process.cwd()}/package.json`);
const { chromium } = require('playwright');

const BASE = process.env.BASE ?? 'http://127.0.0.1:8010';
const LOCALE = process.env.LOCALE ?? 'en';
const PASSWORD = process.env.PASSWORD ?? 'password';
const OUT = `public/images/guide/${LOCALE}`;
mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch();

/** A signed-in page of a demo account */
async function as(email) {
  const page = await browser.newPage({
    viewport: { width: 1280, height: 820 },
    colorScheme: 'light',
    deviceScaleFactor: 1,
  });
  await page.goto(`${BASE}/login`);
  await page.locator('[role=dialog] input[name=email]').waitFor({ timeout: 90_000 });
  await page.fill('[role=dialog] input[name=email]', email);
  await page.fill('[role=dialog] input[name=password]', PASSWORD);
  await page.locator('[role=dialog] [type=submit]').click();
  await page.waitForURL((url) => !url.pathname.startsWith('/login'));
  return page;
}

async function go(page, path) {
  await page.goto(`${BASE}${path}`);
  await page.waitForLoadState('networkidle');
  // Quiet pictures: no motion, no blinking caret, no toasts in the way, the top bar in its
  // place (a whole-page picture would show it wherever the page was scrolled to)
  await page.addStyleTag({
    content: `*, *::before, *::after { animation: none !important; transition: none !important; caret-color: transparent !important; }
      .mantine-Notifications-root { display: none !important; }
      .shell-bar { position: static !important; }`,
  });
  await page.waitForTimeout(400);
}

/** Marks what to look at: an outline and its number */
async function mark(page, selector, number) {
  await page
    .locator(selector)
    .first()
    .evaluate((element, n) => {
      const box = element.getBoundingClientRect();
      const ring = document.createElement('div');
      ring.className = 'guide-mark';
      Object.assign(ring.style, {
        position: 'absolute',
        left: `${box.left + window.scrollX - 6}px`,
        top: `${box.top + window.scrollY - 6}px`,
        width: `${box.width + 12}px`,
        height: `${box.height + 12}px`,
        border: '3px solid #e8590c',
        borderRadius: '12px',
        boxShadow: '0 0 0 4px rgb(232 89 12 / 0.18)',
        zIndex: 10000,
        pointerEvents: 'none',
      });
      const badge = document.createElement('div');
      badge.textContent = String(n);
      Object.assign(badge.style, {
        position: 'absolute',
        left: '-14px',
        top: '-14px',
        width: '26px',
        height: '26px',
        borderRadius: '50%',
        background: '#e8590c',
        color: '#fff',
        font: '800 14px/26px system-ui, sans-serif',
        textAlign: 'center',
      });
      ring.append(badge);
      document.body.append(ring);
    }, number);
}

async function unmark(page) {
  await page.evaluate(() =>
    document.querySelectorAll('.guide-mark').forEach((mark) => mark.remove()),
  );
}

/** The picture: the page, or the part of it around `selector` (with a margin) */
async function shot(page, name, selector) {
  const path = `${OUT}/${name}.jpg`;
  if (selector) {
    // Cut from the whole page (in its coordinates), wherever the part is scrolled to
    const box = await page.locator(selector).first().boundingBox();
    const scrollY = await page.evaluate(() => window.scrollY);
    const margin = 24;
    const x = Math.max(0, box.x - margin);
    await page.screenshot({
      path,
      type: 'jpeg',
      quality: 78,
      fullPage: true,
      clip: {
        x,
        y: Math.max(0, box.y + scrollY - margin),
        width: Math.min(1280 - x, box.width + margin * 2),
        height: box.height + margin * 2,
      },
    });
  } else {
    await page.screenshot({ path, type: 'jpeg', quality: 78 });
  }
  await unmark(page);
  console.log(path);
}

const project = '/projects/1';

// ── A member ──────────────────────────────────────────────────────────────────────────
{
  const page = await as('member@example.com');

  await go(page, project);
  await mark(page, '.stat-link >> nth=0', 1);
  await shot(page, 'member-home');

  await go(page, `${project}/lottery`);
  await mark(page, '[data-tour="ranking.list"]', 1);
  await shot(page, 'member-ranking', '[data-tour="ranking.list"]');
  await page.locator('.rank-slots').scrollIntoViewIfNeeded();
  await mark(page, '.rank-slots', 1);
  await shot(page, 'member-plan-ranking', '.plan-panel');

  await go(page, `${project}/families`);
  await mark(page, '.people-views', 1);
  await shot(page, 'member-people');

  await page.locator('[data-own]').first().click();
  await page.locator('[role=dialog]').waitFor();
  // Mantine moves dialogs in with its own motion: marked once it stands still
  await page.waitForTimeout(900);
  await mark(page, '[role=dialog] button:has(svg.tabler-icon-user-plus)', 1);
  await shot(page, 'member-family', '.mantine-Modal-content');
  await page.keyboard.press('Escape');

  await go(page, `${project}/events`);
  await mark(page, '[data-tour="events.list"]', 1);
  await shot(page, 'member-events');

  await go(page, `${project}/media`);
  await mark(page, '[data-tour="files.share"]', 1);
  await shot(page, 'member-files');

  await go(page, '/settings/profile');
  await page.locator('[role=tab] >> nth=2').click();
  await page.waitForTimeout(300);
  await shot(page, 'member-preferences', '.profile-section >> visible=true');
  await page.close();
}

// ── An admin ──────────────────────────────────────────────────────────────────────────
{
  const page = await as('admin@example.com');

  await go(page, `${project}/manage`);
  await mark(page, '[data-tour="setup.checklist"]', 1);
  await shot(page, 'admin-setup');

  await go(page, `${project}/units`);
  await mark(page, '[data-tour="unit_types.list"]', 1);
  await shot(page, 'admin-units');

  await page.locator('.plan-panel .ds-panel-header button:has(svg.tabler-icon-pencil)').click();
  await page.locator('[aria-label="Plan editor"], [role=application]').first().waitFor();
  await page.waitForTimeout(500);
  await mark(page, '[data-tour="plan.toolbar"]', 1);
  await mark(page, '[data-tour="plan.save"]', 2);
  await shot(page, 'admin-plan', '.plan-panel');
  const shape = page.locator('.plan-shape .plan-item[data-kind="green"] .plan-top').first();
  const box = await shape.boundingBox();
  await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2, { button: 'right' });
  await page.waitForTimeout(400);
  await shot(page, 'admin-plan-menu', '.plan-panel');
  await page.keyboard.press('Escape');
  await page.locator('[data-tour="plan.save"] button').click();
  await page.waitForTimeout(500);

  await go(page, `${project}/manage/invitations`);
  await mark(page, '[data-tour="invite.form"]', 1);
  await shot(page, 'admin-invitations');

  await go(page, `${project}/lottery`);
  await mark(page, '[data-tour="lottery.schedule"]', 1);
  await mark(page, 'button:has([data-tour="lottery.run"])', 2);
  await shot(page, 'admin-lottery');
  await page.close();
}

// ── A superadmin ──────────────────────────────────────────────────────────────────────
{
  const page = await as('superadmin@example.com');

  await go(page, '/admin');
  await mark(page, '[data-tour="approvals.tabs"]', 1);
  await shot(page, 'superadmin-projects');

  await go(page, '/admin/settings');
  await mark(page, 'input[name="lottery_seconds"]', 1);
  await shot(page, 'superadmin-settings');
  await page.close();
}

await browser.close();
