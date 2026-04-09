# After deploy — what you should do (SEO & QA)

Everything below requires **your** production domain, accounts, or manual testing. The app already ships with sitemap, `llms.txt`, meta tags, and legal stubs.

---

## 1. Environment (production server)

1. Set **`APP_URL`** to your real site origin with HTTPS, e.g. `https://otshare.io` (no trailing slash). Wrong `APP_URL` breaks canonical URLs, OG tags, and `llms.txt` links.
2. Set **`APP_ENV=production`** and **`APP_DEBUG=false`**.
3. Run **`php artisan config:cache`** (and `route:cache` if you use it) after changing env.

---

## 2. Social preview image (strongly recommended)

1. Export a **1200×630** PNG (or JPG) — logo + short tagline works well.
2. Host it so it has a **stable HTTPS URL** (e.g. `public/og-landing.png` → `https://yoursite.com/og-landing.png`).
3. In `.env` set:
   - `SEO_OG_IMAGE=https://yoursite.com/og-landing.png`
   - Optionally `SEO_OG_IMAGE_WIDTH=1200` and `SEO_OG_IMAGE_HEIGHT=630`
4. Deploy, then use [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) or [LinkedIn Post Inspector](https://www.linkedin.com/post-inspector/) to **refresh** the cache after the first deploy.

---

## 3. Google Search Console

1. Add your property (domain or URL prefix).
2. Verify ownership (HTML meta tag is supported — set **`SEO_GOOGLE_SITE_VERIFICATION`** in `.env` to the **content** value Google gives you).
3. Submit **`https://yoursite.com/sitemap.xml`** under Sitemaps.
4. After a few days, check **Coverage** / **Pages** for `/`, `/app`, `/download`.

---

## 4. Bing Webmaster Tools (optional)

1. Add site and verify (set **`SEO_MS_VALIDATE`** to Bing’s meta content if you use that method).
2. Submit the same sitemap URL.

---

## 5. Structured data check

1. Open [Google Rich Results Test](https://search.google.com/test/rich-results).
2. Test **`https://yoursite.com/`** — confirm **FAQ** appears and matches the visible FAQ on the page.

---

## 6. Performance spot-check

1. Run [PageSpeed Insights](https://pagespeed.web.dev/) on `/` and `/app` (mobile + desktop).
2. Fix anything critical (usually image weight, caching headers — server/CDN config).

---

## 7. Manual product QA (5 minutes)

1. **Landing** — `/` loads; “Start sharing” → `/app`; “I have a code” → `/download`.
2. **Create share** — upload or text → get code → **Copy link** → open in **private/incognito** → code prefills → unlock succeeds.
3. **QR** (optional) — scan from phone; should open `/download?code=…` and work after prefill.

---

## 8. Legal copy (when you can)

Replace the short **Privacy** and **Terms** stub pages with real policies; keep URLs **`/privacy`** and **`/terms`** so links and sitemap stay valid.

---

## 9. Optional

- **`SEO_TWITTER_HANDLE`** — your X username without `@` for Twitter card attribution.
- **`GA4_MEASUREMENT_ID`** — only loads in production **after** cookie consent (already wired in the Blade layout).

---

## Quick reference URLs (replace with your domain)

| Resource        | URL                    |
|----------------|------------------------|
| Sitemap       | `/sitemap.xml`         |
| Robots        | `/robots.txt`          |
| AI hint file  | `/llms.txt`            |

If anything looks wrong, first verify **`APP_URL`** matches the URL users type in the browser.
