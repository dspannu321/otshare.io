# Landing page (Option B) — implementation checklist

Use this document as the single source of truth while building the marketing landing and keeping the app on dedicated URLs. **Scope changes** should be agreed as a team; **progress** (checkboxes below) should stay current.

**Last reviewed:** `llms.txt` route + [`post-deploy-instructions.md`](post-deploy-instructions.md).  
**Status:** Code-complete. **Your** remaining work is in **[`docs/post-deploy-instructions.md`](post-deploy-instructions.md)** (env, OG image, Search Console, manual QA).

---

## Architecture (locked) — done

| URL | Purpose |
|-----|---------|
| `/` | **Marketing landing** — server-rendered HTML, CTAs, FAQ, SEO-focused copy |
| `/app` | **Upload / create share** — existing React shell (`welcome` + SPA), same behavior today’s home had |
| `/download` | **Unlock / download** — unchanged; **keep this path** so existing unlock links and QR codes (`/download?code=…`) never break |

**Path logic:** `App.jsx` already treats the last path segment as `download` vs everything else as upload — so `/app` → upload and `/download` → download. No change to that rule.

**CTAs on the landing (recommended):**

- Primary: “Start sharing” / “Create a share” → `/app`
- Secondary: “I have a code” → `/download`

Optional footer links: Privacy, Terms — **implemented** (stub pages).

---

## Phase 0 — Prep (before coding)

- [x] **Align primary keyword + positioning** — Reflected in [`config/seo.php`](../config/seo.php) `landing` copy, H1, and hero on [`landing.blade.php`](../resources/views/landing.blade.php) (timed pickup code, no account, file + text, link/QR).
- [ ] **Raster OG image** — Still **pending for production**: export **1200×630 PNG** and set `SEO_OG_IMAGE` in `.env` (SVG fallback exists; raster recommended for social previews).
- [x] **Decide legal stubs** — [`legal-privacy.blade.php`](../resources/views/legal-privacy.blade.php) and [`legal-terms.blade.php`](../resources/views/legal-terms.blade.php) at `/privacy` and `/terms`; replace with full policies when ready.

---

## Phase 1 — Routes & shells

- [x] **Add route** `GET /` → [`landing.blade.php`](../resources/views/landing.blade.php) (not `welcome`).
- [x] **Move app “home”** to `GET /app` → `welcome` + SPA.
- [x] **Keep** `GET /download` → `welcome` (unlock URL unchanged).
- [x] **Update** [`routes/web.php`](../routes/web.php): `landing`, `/app`, `/download`, `/privacy`, `/terms`.
- [x] **Redirects** — `/v2` and `/v2/api` → **`/app`** (old app bookmarks land in the tool); `/v2/download` → `/download`; `/docs` → `/`.
- [x] **Navigation in SPA** — [`App.jsx`](../resources/js/App.jsx): logo → `/`, **Create** → `/app`, **Unlock** → `/download`; footer links Home / Privacy / Terms.

---

## Phase 2 — Landing page content (SSR, SEO-first)

- [x] **Single H1** on `/` — Clear benefit + differentiation (see landing view).
- [x] **Sections** — Hero, **metrics strip**, feature grid, how it works (3 steps), FAQ, CTA band, full-width layout (`max-w-[1400px]` content).
- [x] **FAQ** — Six Q&As with short answers; **matches** `FAQPage` JSON-LD on the same page.
- [x] **Internal links** — CTAs + footer + header to `/app`, `/download`, `/privacy`, `/terms`.
- [x] **No duplicate primary content** — Marketing story on `/`; tool on `/app` / `/download`.

---

## Phase 3 — Meta, canonical, structured data

- [x] **Extend** [`config/seo.php`](../config/seo.php) — `landing`, `app`, `download`, `privacy`, `terms`.
- [x] **Landing Blade** — Title, description, canonical `url('/')`, OG/Twitter (same patterns as welcome).
- [x] **JSON-LD** on `/`:
  - [x] `WebSite` + **`Organization`**
  - [x] **`FAQPage`** aligned with visible FAQ
  - [ ] **`WebApplication`** on `/` — not duplicated on landing (tool schema remains on `/app` / `/download` via `welcome`); acceptable split.
- [ ] **Validate** — **Pending (manual):** run Google [Rich Results Test](https://search.google.com/test/rich-results) on production URL; confirm FAQ matches markup.

---

## Phase 4 — Sitemap, robots, Search Console

- [x] **Sitemap** — [`SeoController`](../app/Http/Controllers/SeoController.php): `/`, `/app`, `/download`, `/privacy`, `/terms` with priorities/changefreq.
- [x] **robots.txt** — Allows `/` (and does not block `/app`).
- [ ] **Post-deploy** — **Pending:** resubmit sitemap in Google Search Console / Bing; spot-check indexing of `/` and `/app`.

---

## Phase 5 — Unlock links & deep links (must not break)

- [x] **Confirm** [`shareLink.js`](../resources/js/shareLink.js) — `buildUnlockUrl()` uses **`/download?code=…`** (unchanged).
- [ ] **Regression** — **Pending (manual QA):** create share → copy link → private window → prefilled code → redeem (and QR scan smoke test on a device).

---

## Phase 6 — Performance & quality

- [x] **Core Web Vitals (baseline)** — Landing: **no React** on `/`; only [`v2.css`](../resources/css/v2.css); hero text SSR.
- [ ] **Lighthouse** — **Pending (manual):** run on `/` and `/app` (mobile + desktop) after deploy.
- [x] **Accessibility (baseline)** — One H1, section `aria-labelledby`, FAQ headings, nav `aria-label`, metrics section `sr-only` heading; contrast aligned with existing v2 theme.

---

## Phase 7 — Tests & CI

- [x] **PHPUnit** — [`SeoTest.php`](../tests/Feature/SeoTest.php): landing FAQ/H1 string, `/app` meta, `/download`, sitemap URLs, canonical with query on download.
- [x] **Welcome / SPA** — [`welcome.blade.php`](../resources/views/welcome.blade.php): `pageKey` **`app`** | **`download`**; canonical `url('/app')` vs `url('/download')`.
- [x] **Example / smoke** — `GET /` returns 200 (landing, not SPA shell-only).

---

## Phase 8 — Optional (later)

- [x] **`/llms.txt`** — Served dynamically by [`SeoController::llms`](../app/Http/Controllers/SeoController.php) (correct `APP_URL` in production). Not a static file under `public/`.
- [ ] **Blog / changelog** — Only if you commit to ongoing content.

---

## Order of work (summary)

1. ~~Routes: `/` landing, `/app` tool upload, `/download` unchanged.~~  
2. ~~SPA nav + redirect tweaks (`/v2` → `/app`).~~  
3. ~~Landing Blade content + CTAs + metrics + full-width UI.~~  
4. ~~`seo.php` + meta + FAQ + JSON-LD.~~  
5. ~~Sitemap~~ — Rich Results + Search Console + Lighthouse + manual link QA **still pending** after deploy.

---

## Out of scope for this checklist

- Paid tiers, billing, accounts  
- Changing unlock URL format away from `/download` without a migration plan  

When production checks (OG image, Rich Results, Search Console, Lighthouse, manual link/QR QA) are done, the technical SEO **launch** checklist for Option B is complete.
