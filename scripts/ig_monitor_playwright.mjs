/**
 * Instagram Monitor — headless browser fetch (stdin JSON → stdout JSON).
 *
 * Input: { "profileUrl": "https://www.instagram.com/username/" }
 * Output: { ok, http_code, effective_url, html, visible_text } | { ok: false, error }
 */

import { chromium } from 'playwright';

const MAX_HTML = 900000;

function normalizeInstagramUrl(raw) {
  const u = String(raw || '').trim();
  try {
    const x = new URL(u);
    if (!/instagram\.com$/i.test(x.hostname) && !/\.instagram\.com$/i.test(x.hostname)) {
      return u;
    }
    x.protocol = 'https:';
    if (x.hostname === 'instagr.am') {
      x.hostname = 'www.instagram.com';
    } else if (x.hostname === 'instagram.com') {
      x.hostname = 'www.instagram.com';
    }
    return x.toString();
  } catch {
    return u;
  }
}

function readStdin() {
  return new Promise((resolve, reject) => {
    const chunks = [];
    process.stdin.on('data', (c) => chunks.push(c));
    process.stdin.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    process.stdin.on('error', reject);
  });
}

async function main() {
  let input;
  try {
    const raw = await readStdin();
    input = JSON.parse(raw);
  } catch (e) {
    process.stdout.write(JSON.stringify({ ok: false, error: 'Invalid JSON on stdin: ' + String(e.message || e) }));
    return;
  }

  let profileUrl = String(input.profileUrl || '').trim();
  if (!profileUrl || !/^https?:\/\//i.test(profileUrl)) {
    process.stdout.write(JSON.stringify({ ok: false, error: 'profileUrl required' }));
    return;
  }

  profileUrl = normalizeInstagramUrl(profileUrl);

  const browser = await chromium.launch({
    headless: true,
    args: [
      '--no-sandbox',
      '--disable-dev-shm-usage',
      '--disable-blink-features=AutomationControlled',
      '--disable-features=IsolateOrigins,site-per-process',
    ],
  });

  try {
    const context = await browser.newContext({
      userAgent:
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
      viewport: { width: 1280, height: 900 },
      locale: 'en-US',
      timezoneId: 'America/New_York',
      colorScheme: 'light',
    });

    const page = await context.newPage();

    let response;
    try {
      response = await page.goto(profileUrl, {
        waitUntil: 'load',
        timeout: 90000,
      });
    } catch (navErr) {
      await browser.close();
      process.stdout.write(
        JSON.stringify({ ok: false, error: 'Navigation failed: ' + String(navErr.message || navErr) })
      );
      return;
    }

    const httpCode = response ? response.status() : 0;
    try {
      await page.waitForSelector('main, article, [role="main"], body', { timeout: 20000 });
    } catch (_) {
      /* SPA */
    }
    // Instagram hydrates slowly; VPS/cold cache needs extra time
    await new Promise((r) => setTimeout(r, 4000));
    await new Promise((r) => setTimeout(r, 4000));
    try {
      await page.evaluate(() => window.scrollTo(0, 400));
    } catch (_) {}
    await new Promise((r) => setTimeout(r, 1500));

    const effectiveUrl = page.url();
    let html = await page.content();
    let visibleText = '';
    try {
      visibleText = await page.evaluate(() => {
        const b = document.body;
        return b && b.innerText ? b.innerText : '';
      });
    } catch (_) {}
    if (visibleText.length > 200000) {
      visibleText = visibleText.slice(0, 200000);
    }

    if (html.length > MAX_HTML) {
      html = html.slice(0, MAX_HTML);
    }

    await browser.close();

    process.stdout.write(
      JSON.stringify({
        ok: true,
        http_code: httpCode,
        effective_url: effectiveUrl,
        html,
        visible_text: visibleText,
      })
    );
  } catch (e) {
    try {
      await browser.close();
    } catch (_) {}
    process.stdout.write(JSON.stringify({ ok: false, error: String(e.message || e) }));
  }
}

main();
