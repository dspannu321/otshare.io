# SEO checklist — otshare.io

Organic growth depends on **relevance**, **quality**, **technical health**, and **authority** (links, brand mentions). Nothing here “guarantees” #1 rankings—use this as a living checklist and review it monthly.

**References:** [Google Search Central](https://developers.google.com/search/docs) · [Bing Webmaster Guidelines](https://www.bing.com/webmasters/help/webmasters-guidelines-30fba23a) · [Schema.org](https://schema.org) · [Open Graph protocol](https://ogp.me/)

---

## Implemented in this repo

| Item | Status | Notes |
|------|--------|--------|
| Unique `<title>` + meta description per `/` and `/download` | Done | `config/seo.php` + `welcome.blade.php` |
| Canonical URLs | Done | `<link rel="canonical">` on public shell |
| `robots.txt` + sitemap URL | Done | Dynamic `/robots.txt` → `/sitemap.xml` |
| XML sitemap (`/`, `/download`) | Done | `SeoController@sitemap` |
| Open Graph + Twitter Card tags | Done | Set `SEO_OG_IMAGE` for large previews |
| JSON-LD (`WebSite` + `WebApplication`) | Done | In `welcome.blade.php` |
| `theme-color`, favicon (`favicon.svg`) | Done | `public/favicon.svg` |
| GA4 (prod-only, consent-gated) | Done | `GA4_MEASUREMENT_ID` + consent banner in `welcome.blade.php` |
| Admin area `noindex` | Done | e.g. admin login |
| Error pages `noindex` | Done | `resources/views/errors/layout.blade.php` |

---

## Configuration (production)

1. **`APP_URL`** — Must be the real **HTTPS** canonical origin (no trailing path). Used for canonical, OG URL, sitemap.
2. **`SEO_OG_IMAGE`** — Full absolute URL to a **1200×630** PNG or JPG (not SVG) for Facebook/LinkedIn-quality previews. Host it on your domain or CDN.
3. **`SEO_TWITTER_HANDLE`** — Username **without** `@`.
4. **`SEO_GOOGLE_SITE_VERIFICATION`** / **`SEO_MS_VALIDATE`** — After claiming the site in Google Search Console and Bing Webmaster Tools.
5. **`GA4_MEASUREMENT_ID`** — Your Google Analytics 4 ID (`G-...`). In this app it loads only in production and only after explicit consent.

---

## On-page quality (ongoing)

| Check | Target |
|-------|--------|
| Title length | ~50–60 characters; unique per important URL |
| Meta description | ~150–160 characters; clear benefit + action |
| H1 | One clear H1 per page (React headings on `/` and `/download`) |
| Duplicate URLs | Avoid `www` vs non-`www` and `http` vs `https` duplicates (redirect in server/DNS) |
| Thin / boilerplate | Add real help content if you add `/docs` or blog later |

---

## Technical & crawl

| Check | Frequency |
|-------|-----------|
| **HTTPS** everywhere, HTTP→HTTPS redirect | Deploy |
| **Sitemap** loads: `GET /sitemap.xml` | Monthly |
| **robots.txt** loads: `GET /robots.txt` | Monthly |
| **404/5xx** rate in Search Console | Monthly |
| **Core Web Vitals** (LCP, INP, CLS) — field + lab | Monthly |
| **Mobile-friendly** | After major UI changes |
| **Structured data** valid — [Rich Results Test](https://search.google.com/test/rich-results) | After template changes |

---

## Search Console & Bing (required for measurement)

| Step | Owner | Done |
|------|-------|------|
| Add property (Domain or URL prefix) | | ☐ |
| Verify ownership (HTML tag → `SEO_GOOGLE_SITE_VERIFICATION`) | | ☐ |
| Submit sitemap `https://YOUR_DOMAIN/sitemap.xml` | | ☐ |
| Repeat in **Bing Webmaster Tools** (import from Google or verify separately) | | ☐ |
| Monitor **Performance** (queries, CTR, position), **Coverage**, **Page experience** | | ☐ |

---

## Analytics (pick one, keep privacy policy updated)

| Tool | Use for |
|------|---------|
| Google Analytics 4 | Traffic, landing pages, conversions |
| Plausible / Fathom / Cloudflare Web Analytics | Lighter, privacy-oriented |
| Search Console | Queries & Google-specific issues (not a full analytics replacement) |

---

## Authority & distribution (organic growth)

| Tactic | Notes |
|--------|--------|
| Product listings / directories | Relevant dev/security communities only; avoid spam directories |
| Helpful content | Changelog, security practices, how pickup codes work |
| Social proof | Honest comparisons; link to your site with consistent branding |
| Backlinks | Quality > quantity; avoid paid bulk links |

---

## Monthly performance review (copy row)

| Month | GSC clicks | GSC impressions | Avg position | Indexed pages | Notes / actions |
|-------|------------|-----------------|--------------|---------------|-----------------|
| | | | | | |

**Optional columns:** Bing clicks, GA4 sessions, Core Web Vitals pass %, Lighthouse performance score.

---

## Quarterly

- [ ] Review top queries in GSC — align titles/descriptions with intent where CTR is low.
- [ ] Check for **manual actions** / security issues in GSC.
- [ ] Re-run Lighthouse on `/` and `/download` (mobile + desktop).
- [ ] Confirm `SEO_OG_IMAGE` still loads (broken images hurt shares).

---

## Honest note on “rank at the top”

Search engines rank pages that **best satisfy intent** for a query, on a **trusted** site with **strong signals**. Technical SEO removes obstacles; it does not replace useful content, reputation, and time. Treat this file as **instrumentation + hygiene** so you can measure and improve deliberately.
