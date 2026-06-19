// Page discovery for Stoplight docs sites.
//
// We try three independent sources and union them, preferring the order that
// best reflects the published table of contents:
//
//   1. The rendered left-hand navigation on the entry page (DOM order ==
//      reading order). Stoplight renders the full sidebar on every page, so
//      one page usually yields the whole, correctly-ordered TOC.
//   2. The Stoplight platform table-of-contents API, when we can resolve the
//      project id embedded in the page bootstrap.
//   3. sitemap.xml (completeness backstop; no meaningful order).
//
// All network calls run *inside the real browser page* (page.evaluate(fetch))
// so they inherit the browser's TLS fingerprint, cookies and User-Agent and
// get past the bot-blocking that rejects plain HTTP clients.

import { log } from './util.js';

// Derive the docs "base path" used to tell doc links apart from marketing /
// external links. e.g. https://docs.x.com/docs/platform/abc  ->  "/docs"
export function deriveBasePath(entryUrl) {
  const u = new URL(entryUrl);
  const seg = u.pathname.split('/').filter(Boolean);
  return seg.length ? `/${seg[0]}` : '/';
}

// Ordered anchors from the rendered page, filtered to same-origin doc links.
export async function discoverFromSidebar(page, entryUrl, basePath) {
  const origin = new URL(entryUrl).origin;
  const hrefs = await page.evaluate(() => {
    const out = [];
    for (const a of document.querySelectorAll('a[href]')) {
      const href = a.getAttribute('href');
      if (href) out.push(href);
    }
    return out;
  });

  const seen = new Set();
  const urls = [];
  for (const href of hrefs) {
    let abs;
    try {
      abs = new URL(href, entryUrl);
    } catch {
      continue;
    }
    if (abs.origin !== origin) continue;
    if (basePath !== '/' && !abs.pathname.startsWith(basePath)) continue;
    abs.hash = '';
    const key = abs.pathname + abs.search;
    if (seen.has(key)) continue;
    seen.add(key);
    urls.push(abs.toString());
  }
  log.debug(`sidebar: ${urls.length} candidate doc links`);
  return urls;
}

// Best-effort sitemap.xml (handles plain sitemaps and sitemap indexes).
export async function discoverFromSitemap(page, entryUrl) {
  const origin = new URL(entryUrl).origin;
  const collected = new Set();

  async function fetchXml(url) {
    return page.evaluate(async (u) => {
      try {
        const r = await fetch(u, { credentials: 'include' });
        if (!r.ok) return null;
        return await r.text();
      } catch {
        return null;
      }
    }, url);
  }

  const locsOf = (xml) =>
    [...xml.matchAll(/<loc>\s*([^<\s]+)\s*<\/loc>/gi)].map((m) => m[1]);

  const root = await fetchXml(`${origin}/sitemap.xml`);
  if (!root) {
    log.debug('sitemap: none found');
    return [];
  }
  const locs = locsOf(root);
  const isIndex = /<sitemapindex/i.test(root);
  if (isIndex) {
    for (const sm of locs) {
      const child = await fetchXml(sm);
      if (child) for (const u of locsOf(child)) collected.add(u);
    }
  } else {
    for (const u of locs) collected.add(u);
  }

  const same = [...collected].filter((u) => {
    try {
      return new URL(u).origin === origin;
    } catch {
      return false;
    }
  });
  log.debug(`sitemap: ${same.length} same-origin URLs`);
  return same;
}

// Try to resolve the Stoplight project id from the page bootstrap and pull the
// table-of-contents API. Returns ordered slugs/URLs, or [] if not resolvable.
// Stoplight's hosted frontend calls:
//   GET /api/v1/projects/{projectId}/table-of-contents
// and the project id is injected into the page (window/Next.js bootstrap).
export async function discoverFromStoplightToc(page, entryUrl) {
  const origin = new URL(entryUrl).origin;

  const projectId = await page.evaluate(() => {
    // Common places Stoplight stashes the active project id.
    const tryPaths = [
      () => window.__NEXT_DATA__?.props?.pageProps?.projectId,
      () => window.__NEXT_DATA__?.query?.projectId,
      () => window.__STOPLIGHT__?.projectId,
      () => window.PRELOADED_STATE?.projectId,
    ];
    for (const f of tryPaths) {
      try {
        const v = f();
        if (v) return String(v);
      } catch {
        /* ignore */
      }
    }
    // Fall back to scraping the serialized bootstrap blobs.
    const html = document.documentElement.innerHTML;
    const m =
      html.match(/"projectId"\s*:\s*"?(\d+)"?/) ||
      html.match(/projects\/(\d+)\/(?:nodes|table-of-contents)/);
    return m ? m[1] : null;
  });

  if (!projectId) {
    log.debug('toc-api: could not resolve project id');
    return [];
  }
  log.debug(`toc-api: project id ${projectId}`);

  const toc = await page.evaluate(async (url) => {
    try {
      const r = await fetch(url, { credentials: 'include' });
      if (!r.ok) return null;
      return await r.json();
    } catch {
      return null;
    }
  }, `${origin}/api/v1/projects/${projectId}/table-of-contents`);

  if (!toc) {
    log.debug('toc-api: table-of-contents fetch failed');
    return [];
  }

  // The TOC is a nested structure of groups/items; walk it in order and pull
  // anything that carries a slug/uri we can turn into a doc URL.
  const urls = [];
  const seen = new Set();
  const walk = (node) => {
    if (!node || typeof node !== 'object') return;
    const slug = node.slug || node.uri || node.url;
    if (typeof slug === 'string' && /^[\w./-]+$/.test(slug)) {
      let abs;
      try {
        abs = new URL(slug, origin).toString();
      } catch {
        abs = null;
      }
      if (abs && !seen.has(abs)) {
        seen.add(abs);
        urls.push(abs);
      }
    }
    const kids = node.items || node.children || node.nodes;
    if (Array.isArray(kids)) kids.forEach(walk);
  };
  const top = toc.items || toc.data?.items || toc.toc || toc;
  (Array.isArray(top) ? top : [top]).forEach(walk);

  log.debug(`toc-api: ${urls.length} URLs`);
  return urls;
}

// Combine sources into one ordered, de-duplicated, filtered list.
export function combine({ ordered = [], extra = [] }, { basePath, origin, include, exclude }) {
  const seen = new Set();
  const out = [];
  const accept = (u) => {
    let abs;
    try {
      abs = new URL(u);
    } catch {
      return;
    }
    if (abs.origin !== origin) return;
    if (basePath !== '/' && !abs.pathname.startsWith(basePath)) return;
    const full = abs.origin + abs.pathname + abs.search;
    if (include && !include.test(full)) return;
    if (exclude && exclude.test(full)) return;
    if (seen.has(full)) return;
    seen.add(full);
    out.push(full);
  };
  ordered.forEach(accept);
  extra.forEach(accept);
  return out;
}
