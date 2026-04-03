# Trackify panel (project root)

Auth and the generator UI live in the **same folder as `index.php`** (the trap entry).

| URL | Purpose |
|-----|---------|
| `/sign-in.php` | Log in |
| `/sign-up.php` | Register |
| `/panel.html` | Dashboard (open after sign-in so `api.php` gets your session cookie) |
| `/api.php` | JSON API for the panel |
| `/account.php` | Redirect: signed in → `panel.html`, else → `sign-in.php` |

**Why `panel.html` and not `index.html`?**  
Apache often serves `index.html` before `index.php` for `/`. The trap must use `index.php`, so the control UI is named `panel.html` to avoid breaking the public link.

**Local server:** `serve.cmd` / `php -S 127.0.0.1:8000` from this folder → `http://127.0.0.1:8000/panel.html` (keep that terminal open; closing it stops the site).

DB schema: `schema.sql` · Config: `config.php` / `config.example.php`
