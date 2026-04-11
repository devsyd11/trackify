/**
 * Facebook Monitor — Playwright fetch (stdin JSON → stdout JSON).
 *
 * Input (stdin, one JSON object):
 *   { "profileUrl": "https://www.facebook.com/username", "cookies": "..." }
 *
 * cookies: flat "a=b; c=d" or JSON array from cookie export extensions.
 *
 * Output (stdout, one JSON object):
 *   { "ok": true, "http_code": 200, "effective_url": "...", "html": "..." }
 *   { "ok": false, "error": "..." }
 */

import { chromium } from 'playwright';

const MAX_HTML = 900000;

function readStdin() {
  return new Promise((resolve, reject) => {
    const chunks = [];
    process.stdin.on('data', (c) => chunks.push(c));
    process.stdin.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    process.stdin.on('error', reject);
  });
}

function normalizeCookieString(raw) {
  const s = String(raw || '').trim();
  if (!s) return '';
  if (s[0] === '[') {
    try {
      const arr = JSON.parse(s);
      if (!Array.isArray(arr)) return '';
      const parts = [];
      for (const item of arr) {
        if (!item || typeof item !== 'object') continue;
        const name = String(item.name || '');
        const value = String(item.value ?? '');
        if (name) parts.push(`${name}=${value}`);
      }
      return parts.join('; ');
    } catch {
      return '';
    }
  }
  return s;
}

function cookiesToPlaywrightList(flat, defaultDomain = '.facebook.com') {
  const out = [];
  for (const part of flat.split(';')) {
    const p = part.trim();
    if (!p) continue;
    const eq = p.indexOf('=');
    if (eq <= 0) continue;
    const name = p.slice(0, eq).trim();
    const value = p.slice(eq + 1).trim();
    if (!name) continue;
    out.push({
      name,
      value,
      domain: defaultDomain,
      path: '/',
    });
  }
  return out;
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

  const profileUrl = String(input.profileUrl || '').trim();
  const cookiesRaw = input.cookies != null ? String(input.cookies) : '';

  if (!profileUrl || !/^https?:\/\//i.test(profileUrl)) {
    process.stdout.write(JSON.stringify({ ok: false, error: 'profileUrl required' }));
    return;
  }

  const flat = normalizeCookieString(cookiesRaw);
  if (!flat) {
    process.stdout.write(JSON.stringify({ ok: false, error: 'cookies empty' }));
    return;
  }

  const cookieList = cookiesToPlaywrightList(flat);

  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const context = await browser.newContext({
      userAgent:
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
      viewport: { width: 1280, height: 800 },
      locale: 'en-US',
      timezoneId: 'UTC',
    });

    if (cookieList.length > 0) {
      await context.addCookies(cookieList);
    }

    const page = await context.newPage();

    let response;
    try {
      response = await page.goto(profileUrl, {
        waitUntil: 'domcontentloaded',
        timeout: 75000,
      });
    } catch (navErr) {
      await browser.close();
      process.stdout.write(
        JSON.stringify({ ok: false, error: 'Navigation failed: ' + String(navErr.message || navErr) })
      );
      return;
    }

    const httpCode = response ? response.status() : 0;
    await new Promise((r) => setTimeout(r, 1500));
    // Let client-rendered “content not available” / profile chrome appear in the DOM
    await new Promise((r) => setTimeout(r, 2000));

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
